<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Over</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            text-align: center;
            padding: 40px;
        }
        .container {
            background: white;
            border-radius: 10px;
            display: inline-block;
            padding: 30px 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .stats { margin-top: 20px; }
        .stats p { font-size: 1.1em; margin: 8px 0; }
        .words { margin-top: 30px; text-align: left; display: inline-block; }
        a {
            display: inline-block;
            margin-top: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }
        a:hover { background-color: #45a049; }
    </style>
</head>
<body>
<div class="container">
    <h1>Game Over!</h1>

    <p><strong>Target Word:</strong> <?= htmlspecialchars($_SESSION['game']['target'] ?? 'Unknown') ?></p>
    <p><strong>Final Score:</strong> <?= htmlspecialchars($_SESSION['game']['score'] ?? 0) ?></p>

    <div class="stats">
        <h2>Your Stats</h2>
        <p><strong>Games Played:</strong> <?= htmlspecialchars($stats['gamesPlayed'] ?? 0) ?></p>
        <p><strong>Win %:</strong> <?= htmlspecialchars($stats['winPct'] ?? 0) ?>%</p>
        <p><strong>Highest Score:</strong> <?= htmlspecialchars($stats['highScore'] ?? 0) ?></p>
        <p><strong>Average Score:</strong> <?= htmlspecialchars(number_format($stats['avgScore'] ?? 0, 1)) ?></p>
    </div>

    <div class="words">
        <h3>Valid Words You Found:</h3>
        <?php
        $validGuesses = array_filter($_SESSION['game']['all_guesses'] ?? [], fn($g) => ($g['valid'] ?? false));
        if (!empty($validGuesses)): ?>
            <ul>
                <?php foreach ($validGuesses as $g): ?>
                    <li><?= htmlspecialchars($g['word']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>None</p>
        <?php endif; ?>
    </div>

    <a href="index.php?command=start">Play Again</a>
    <a href="index.php?command=quit">Quit</a>
</div>
</body>
</html>
