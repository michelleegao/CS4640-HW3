<?php
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Anagrams Game</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            font-family:system-ui,Arial;
            margin:2rem;
        }
        header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:1rem;
        }
        .letters{
            font-size:2.2rem;
            letter-spacing:.4rem;
            border:1px solid #ddd;
            padding:1rem;
            border-radius:8px;
            display:inline-block;
            margin:.5rem 0;
        }
        .row{
            display:flex;
            gap:2rem;
            align-items:
            flex-start;
        }
        .panel{
            border:1px solid #ddd;
            border-radius:8px;
            padding:1rem;
        }
        ul{
            margin:.4rem 0 0 1rem;
        }
        .msg{
            color:#064;
            margin:.5rem 0;
        }
        .err{
            color:#a00;
            margin:.5rem 0;
        }
        .stats small{
            display:block;
            color:#555;
            margin-top:.2rem;
        }
    </style>
</head>
<body>
<header>
    <div>
        <strong><?= htmlspecialchars($user['name']) ?></strong> (<?= htmlspecialchars($user['email']) ?>)
    </div>
    <nav>
        <a href="index.php?command=reshuffle">Reshuffle</a> |
        <a href="index.php?command=quit">Quit Game</a> |
        <a href="index.php?command=logout">Logout</a>
    </nav>
</header>

<h2>Score: <?= $_SESSION['game']['score'] ?></h2>
<div class="letters"><?= htmlspecialchars(implode(' ', array_map('strtoupper', $letters))) ?></div>

<?php if (!empty($message)): ?>
    <div class="msg"><?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="err"><?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="post" action="index.php?command=guess">
    <label>Enter a word using these letters:</label>
    <input name="guess" autofocus>
    <button type="submit">Submit Guess</button>
</form>

<form method="post" action="index.php?command=shuffle" style="display:inline">
    <button type="submit">Re-shuffle</button>
</form>

<a href="index.php?command=quit">Quit (end session)</a>

<div class="row" style="margin-top:1rem;">
    <div class="panel">
        <strong>Valid words found: (<?= count($_SESSION['game']['guessed']) ?>)</strong>
        <ul>
            <?php foreach($_SESSION['game']['guessed'] as $w): ?>
                <li><?= htmlspecialchars($w) ?></li>
                <?php endforeach; ?>
        </ul>
    </div>

    <div class="panel stats">
        <strong>Your stats</strong>
        <small>Games played: <?= (int)$stats['games_played'] ?></small>
        <small>Win %: <?= number_format((float)$stats['win_pct']*100, 0) ?>%</small>
        <small>Highest score: <?= (int)$stats['max_score'] ?></small>
        <small>Average score: <?= number_format((float)$stats['avg_score'], 1) ?></small>
    </div>
</div>

<p style="margin-top:1.5rem;">
    Guessing the 7-letter target word will end the game (you win).
</p>

</body>
</html>
