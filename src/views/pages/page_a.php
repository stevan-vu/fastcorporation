<?php include(__DIR__ . "/../header.php"); ?>


    <?php if(!$bought): ?>
    <form action="/page-a" method="POST">
        <?php include __DIR__ . '/../csrf_field.php'; ?>
        <button>Buy a cow</button>
    </form>
    <?php else: ?>
        thankYou
    <?php endif; ?>

<?php include(__DIR__ . "/../footer.php"); ?>