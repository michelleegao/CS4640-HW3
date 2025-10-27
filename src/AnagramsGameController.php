<?php

class AnagramsGameController {
    private $viewsPath;
    private $words7Path;
    private $wordBankPath;

    public function __construct() {
        $this->viewsPath = __DIR__ . '/../views/';
        $this->words7Path = __DIR__ . '/words7.txt';
        $this->wordBankPath = __DIR__ . '/word_bank.json';
    }

    public function handleRequest() {
        $cmd = $_GET['command'] ?? 'welcome';

        // simple routing
        switch ($cmd) {
            case 'login':
                $this->doLogin();
                break;
            case 'start':
                $this->startNewGame();
                break;
            case 'guess':
                $this->handleGuess();
                break;
            case 'shuffle':
                $this->shuffleLetters();
                break;
            case 'quit':
                $this->quitGame();
                break;
            case 'gameover':
                $this->showGameOver();
                break;
            case 'welcome':
            default:
                $this->showWelcome();
                break;
        }
    }

    /* ---------- Views ---------- */
    private function showWelcome($message = '') {
        include $this->viewsPath . 'welcome.php';
    }

    private function showGame() {
        if (!isset($_SESSION['user'])) {
            $this->showWelcome('Please log in first.');
            return;
        }
        include $this->viewsPath . 'game.php';
    }

    private function showGameOver($message = '') {
        include $this->viewsPath . 'gameover.php';
    }

    /* ---------- Auth / Session ---------- */
    private function doLogin() {
        // use POST to avoid sending passwords in URL
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showWelcome();
            return;
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$password) {
            $this->showWelcome('Please provide name, email and password.');
            return;
        }

        if (!isset($_SESSION['users'])) $_SESSION['users'] = [];

        // check if user exists
        foreach ($_SESSION['users'] as $u) {
            if (strtolower($u['email']) === strtolower($email)) {
                // verify password
                if (password_verify($password, $u['password_hash'])) {
                    $_SESSION['user'] = ['name' => $u['name'], 'email' => $u['email']];
                    $this->startNewGame(); // login successful
                    return;
                } else {
                    $this->showWelcome('Wrong password for that email.');
                    return;
                }
            }
        }

        // new user
        $passHash = password_hash($password, PASSWORD_DEFAULT);
        $newUser = ['name' => $name, 'email' => $email, 'password_hash' => $passHash];
        $_SESSION['users'][] = $newUser;
        $_SESSION['user'] = ['name' => $name, 'email' => $email];

        $this->startNewGame();
    }

    /* ---------- Game lifecycle ---------- */
    private function startNewGame() {
        if (!isset($_SESSION['user'])) {
            $this->showWelcome('Please log in first.');
            return;
        }

        // pick random 7-letter word not already played by this user (dev: just random)
        $all = $this->loadWords7();
        if (!$all) {
            $this->showWelcome('Word list missing.');
            return;
        }

        // pick a target word
        $target = $all[array_rand($all)];

        // create game session state
        $_SESSION['game'] = [
            'target' => $target,
            'shuffled' => $this->shuffleString($target),
            'score' => 0,
            'guessed' => [],       // valid guessed words (unique)
            'invalid_count' => 0,
            'all_guesses' => []    // record each guess and whether valid
        ];

        $this->showGame();
    }

    private function quitGame() {
        // mark game as lost if it exists
        if (isset($_SESSION['game'])) {
        }
        session_unset();
        // show welcome
        $this->showWelcome('Session ended. You may log in again.');
    }

    /* ---------- Guess handling ---------- */
    private function handleGuess() {
        if (!isset($_SESSION['game'])) {
            $this->showWelcome('No active game. Start a new one.');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showGame();
            return;
        }
        $guess = trim($_POST['guess'] ?? '');
        if ($guess === '') {
            $this->showGame();
            return;
        }

        $guessLower = strtolower($guess);
        $target = strtolower($_SESSION['game']['target']);

        // check letters allowed
        if (!$this->lettersAllowed($guessLower, $target)) {
            $_SESSION['game']['invalid_count']++;
            $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => false, 'reason' => 'disallowed_letters'];
            $this->showGame();
            return;
        }

        // check against short-word bank
        $wordBank = $this->loadWordBank();
        if (!in_array($guessLower, $wordBank)) {
            $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => false, 'reason' => 'not_in_word_bank'];
            // optional penalty: -1
            $_SESSION['game']['score'] -= 1;
            $this->showGame();
            return;
        }

        // valid word: check not previously guessed
        if (in_array($guessLower, $_SESSION['game']['guessed'])) {
            // already guessed — no scoring change
            $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => false, 'reason' => 'already_guessed'];
            $this->showGame();
            return;
        }

        // valid and new
        $len = strlen($guessLower);
        $pts = $this->pointsForLength($len);
        $_SESSION['game']['score'] += $pts;
        $_SESSION['game']['guessed'][] = $guessLower;
        $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => true, 'points' => $pts];

        // if full 7-letter word guessed, game over (win)
        if ($len === 7 && $guessLower === $target) {
            // For DB: record the completed game (won=true)
            $_SESSION['game']['won'] = true;
            $this->showGameOver();
            return;
        }

        // else stay on game page
        $this->showGame();
    }

    private function shuffleLetters() {
        if (!isset($_SESSION['game'])) {
            $this->showWelcome('No active game.');
            return;
        }
        $target = $_SESSION['game']['target'];
        $_SESSION['game']['shuffled'] = $this->shuffleString($target);
        $this->showGame();
    }

    /* ---------- Helpers ---------- */

    private function loadWords7() {
        if (!file_exists($this->words7Path)) return [];
        $lines = file($this->words7Path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $clean = array_map('trim', $lines);
        // ensure lower-case and 7-letter
        $clean = array_filter($clean, function($w){ return strlen(trim($w))===7; });
        return array_values(array_map('strtolower', $clean));
    }

    private function loadWordBank() {
        if (!file_exists($this->wordBankPath)) return [];
        $json = file_get_contents($this->wordBankPath);
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        // normalize to lowercase
        return array_map('strtolower', $arr);
    }

    private function lettersAllowed($guess, $target) {
        // ensure guess can be made from target characters (counts matter)
        $g = count_chars($guess, 1);
        $t = count_chars($target, 1);
        foreach ($g as $ascii => $count) {
            $char = chr($ascii);
            if (!isset($t[$ascii]) || $count > $t[$ascii]) return false;
        }
        return true;
    }

    private function pointsForLength($len) {
        switch ($len) {
            case 1: return 1;
            case 2: return 2;
            case 3: return 4;
            case 4: return 8;
            case 5: return 15;
            case 6: return 30;
            case 7: return 50;
            default: return 0;
        }
    }

    private function shuffleString($s) {
        $a = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        shuffle($a);
        return implode('', $a);
    }
}
