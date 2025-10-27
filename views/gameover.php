<?php $user = $_SESSION['user']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Anagrams Game Over</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            font-family:system-ui,Arial;
            margin:2rem;
        }
        .panel{
            border:1px solid #ffffffff;
            border-radius:8px;
            padding:1rem;
            max-width:600px;
        }
        ul{
            margin:.4rem 0 0 1rem;
        }
    </style>
</head>
<body>
    <h1>Game Over</h1>
    <div class="panel">
        <p><strong>Player:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
        <p><strong>Target word:</strong> <?= htmlspecialchars(strtoupper($last['target'])) ?></p>
        <p><strong>Final score:</strong> <?= (int)$last['score'] ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($last['status']) ?></p>
        <p><strong>Valid words you found:</strong></p>
        <ul>
        <?php foreach ($last['valid'] as $w): ?>
            <li><?= htmlspecialchars($w) ?></li>
        <?php endforeach; ?>
        <?php if (empty($last['valid'])): ?>
            <li><em>None</em></li>
        <?php endif; ?>
        </ul>
    </div>

    <div style="margin-top:1rem;">
        <a href="index.php?command=game">Play again</a> |
        <a href="index.php?command=logout">Exit to Welcome</a>
    </div>

    <hr>
    <h3>Your overall stats</h3>
    <p>Games played: <?= (int)$stats['games_played'] ?> |
        Win %: <?= number_format((float)$stats['win_pct']*100, 0) ?>% |
        Highest: <?= (int)$stats['max_score'] ?> |
        Average: <?= number_format((float)$stats['avg_score'], 1) ?>
    </p>
</body>
</html>
