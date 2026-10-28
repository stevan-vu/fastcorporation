<?php include(__DIR__ . "/../header.php"); ?>

    <div id="form-container">
        <h1>Login</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/login">
            <?php include __DIR__ . '/../csrf_field.php'; ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log in</button>
        </form>

        <p class="hint">No account? <a href="/register">Register</a></p>
    </div>

<?php include(__DIR__ . "/../footer.php"); ?>
