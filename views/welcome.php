<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Anagrams Welcome/title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            font-family:system-ui,Arial;
            margin:2rem;
        }
        .box{
            max-width:480px;
            border:1px solid #ffffffff;
            border-radius:8px;
            padding:1rem;
        }
        .err{
            color:#a00;
            margin:.5rem 0;
        }
        label{
            display:block;
            margin:.5rem 0 .2rem;
        }
        input{
            width:100%;
            padding:.5rem;
            margin-bottom:.5rem;
        }
        button{
            padding:.6rem 1rem;
        }
    </style>
</head>
<body>
    <h1>Come play Anagrams!</h1>
    <div class="box">
        <?php if (!empty($error)): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?command=login">
            <label for="name">Name</label>
            <input id="name" name="name" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Start Playing</button>
        </form>
    </div>
</body>
</html>
