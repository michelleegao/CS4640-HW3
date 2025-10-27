<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Config.php';

class AnagramsGameController {
    private $viewsPath;
    private $words7Path;
    private $wordBankPath;

    public function __construct() {
        $this->viewsPath = __DIR__ . '/../views/';
        $this->words7Path = Config::wordsPath();
        $this->wordBankPath = Config::bankPath();
    }

    public function run() {
        $cmd = $_GET['command'] ?? 'welcome';

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

        // Define letters for display
        if (isset($_SESSION['game']['shuffled'])) {
            $letters = str_split($_SESSION['game']['shuffled']);
        } elseif (isset($_SESSION['game']['target'])) {
            $letters = str_split($_SESSION['game']['target']);
        } else {
            $letters = [];
        }

        // Fetch user stats
        $userId = $_SESSION['user']['id'];
        $stats = $this->getUserStats($userId);

        include $this->viewsPath . 'game.php';
    }

    private function showGameOver($message = '') {
        include $this->viewsPath . 'gameover.php';
    }

    /* ---------- Auth / Session ---------- */
    private function doLogin() {
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

        // Check if user exists
        $user = Database::one("SELECT * FROM hw3_users WHERE email = :email", [":email" => $email]);

        if ($user) {
            if (!password_verify($password, $user['password_hash'])) {
                $this->showWelcome('Wrong password for that email.');
                return;
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ];
            $this->startNewGame();
            return;
        }

        // New user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id = Database::insertReturningId(
            "INSERT INTO hw3_users (name, email, password_hash)
             VALUES (:n, :e, :p)
             RETURNING id",
            [":n" => $name, ":e" => $email, ":p" => $hash]
        );

        $_SESSION['user'] = [
            'id' => $id,
            'name' => $name,
            'email' => $email
        ];

        $this->startNewGame();
    }

    /* ---------- Game lifecycle ---------- */
    private function startNewGame() {
        if (!isset($_SESSION['user'])) {
            $this->showWelcome('Please log in first.');
            return;
        }

        $all = $this->loadWords7();
        if (!$all) {
            $this->showWelcome('Word list missing.');
            return;
        }

        $userId = $_SESSION['user']['id'];

        // Get all words the user has already played
        $playedRows = Database::all(
            "SELECT target_word FROM hw3_games WHERE user_id = :u",
            [":u" => $userId]
        );
        $played = array_map(fn($r) => strtolower(trim($r['target_word'])), $playedRows);

        // Filter out already played words
        $available = array_diff($all, $played);

        if (empty($available)) {
            $this->showGameOver("You've played all available words!");
            return;
        }

        // Pick a new random target word
        $target = $available[array_rand($available)];

        // Ensure word exists in hw3_words
        Database::execStmt(
            "INSERT INTO hw3_words (word) VALUES (:w) ON CONFLICT DO NOTHING",
            [":w" => strtolower(trim($target))]
        );

        $gameId = Database::insertReturningId(
            "INSERT INTO hw3_games (user_id, target_word, score, status)
            VALUES (:u, :w, 0, 'in_progress')
            RETURNING id",
            [":u" => $userId, ":w" => strtolower(trim($target))]
        );

        // Store session data
        $_SESSION['game'] = [
            'id' => $gameId,
            'target' => $target,
            'shuffled' => $this->shuffleString($target),
            'score' => 0,
            'guessed' => [],
            'invalid_count' => 0,
            'all_guesses' => []
        ];

        $this->showGame();
    }


    private function quitGame() {
        if (isset($_SESSION['game']['id'])) {
            Database::execStmt(
                "UPDATE hw3_games SET status = 'lost', score = :s WHERE id = :id",
                [":s" => $_SESSION['game']['score'] ?? 0, ":id" => $_SESSION['game']['id']]
            );
        }
        session_unset();
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

        // Check letters allowed
        if (!$this->lettersAllowed($guessLower, $target)) {
            $_SESSION['game']['invalid_count']++;
            $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => false, 'reason' => 'disallowed_letters'];
            $this->showGame();
            return;
        }

        // Check against word bank
        $wordBank = $this->loadWordBank();
        if (!in_array($guessLower, $wordBank)) {
            $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => false, 'reason' => 'not_in_word_bank'];
            $_SESSION['game']['score'] -= 1;
            $this->showGame();
            return;
        }

        // Valid but repeated
        if (in_array($guessLower, $_SESSION['game']['guessed'])) {
            $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => false, 'reason' => 'already_guessed'];
            $this->showGame();
            return;
        }

        // Valid and new
        $len = strlen($guessLower);
        $pts = $this->pointsForLength($len);
        $_SESSION['game']['score'] += $pts;
        $_SESSION['game']['guessed'][] = $guessLower;
        $_SESSION['game']['all_guesses'][] = ['word' => $guessLower, 'valid' => true, 'points' => $pts];

        // If 7-letter correct word guessed, mark win
        if ($len === 7 && trim(strtolower($guessLower)) === trim(strtolower($_SESSION['game']['target']))) {
            $_SESSION['game']['won'] = true;

            Database::execStmt(
                "UPDATE hw3_games SET status = 'won', score = :s WHERE id = :id",
                [":s" => $_SESSION['game']['score'], ":id" => $_SESSION['game']['id']]
            );

            $this->showGameOver();
            return;
        }

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
        $clean = array_filter($clean, fn($w) => strlen(trim($w)) === 7);
        return array_values(array_map('strtolower', $clean));
    }

    private function loadWordBank() {
        if (!file_exists($this->wordBankPath)) return [];
        $json = file_get_contents($this->wordBankPath);
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];

        $flat = [];
        foreach ($arr as $subArr) {
            if (is_array($subArr)) {
                foreach ($subArr as $word) {
                    if (is_string($word)) $flat[] = strtolower($word);
                }
            }
        }
        return $flat;
    }

    private function lettersAllowed($guess, $target) {
        $g = count_chars($guess, 1);
        $t = count_chars($target, 1);
        foreach ($g as $ascii => $count) {
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

    private function getUserStats($userId) {
        // Total games played
        $gamesPlayed = Database::one(
            "SELECT COUNT(*) AS total FROM hw3_games WHERE user_id = :u",
            [":u" => $userId]
        )['total'] ?? 0;

        // Games won
        $gamesWon = Database::one(
            "SELECT COUNT(*) AS won FROM hw3_games WHERE user_id = :u AND status = 'won'",
            [":u" => $userId]
        )['won'] ?? 0;

        // Highest score
        $highScore = Database::one(
            "SELECT COALESCE(MAX(score), 0) AS max_score FROM hw3_games WHERE user_id = :u",
            [":u" => $userId]
        )['max_score'] ?? 0;

        // Average score
        $avgScore = Database::one(
            "SELECT COALESCE(AVG(score), 0) AS avg_score FROM hw3_games WHERE user_id = :u",
            [":u" => $userId]
        )['avg_score'] ?? 0;

        // Win percentage
        $winPct = $gamesPlayed > 0 ? round(($gamesWon / $gamesPlayed) * 100, 1) : 0;

        return [
            'gamesPlayed' => $gamesPlayed,
            'winPct' => $winPct,
            'highScore' => $highScore,
            'avgScore' => $avgScore
        ];
    }

}
