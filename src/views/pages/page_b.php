<?php include(__DIR__ . "/../header.php"); ?>

    <form action="/page-b" method="POST">
        <?php include __DIR__ . '/../csrf_field.php'; ?>
        <button>Download</button>
    </form>


<?php include(__DIR__ . "/../footer.php"); ?>