<?php include(__DIR__ . "/../header.php"); ?>

    <div id="form-container">
        <h1>Register</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/register">
            <?php include __DIR__ . '/../csrf_field.php'; ?>
            <label for="firstName">First name</label>
            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($firstName ?? '') ?>" maxlength="255" autocomplete="given-name" required autofocus>

            <label for="lastName">Last name</label>
            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($lastName ?? '') ?>" maxlength="255" autocomplete="family-name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" maxlength="255" autocomplete="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>

            <button type="submit">Register</button>
        </form>

        <p class="hint">Already have an account? <a href="/login">Log in</a></p>
    </div>

<?php include(__DIR__ . "/../footer.php"); ?>
