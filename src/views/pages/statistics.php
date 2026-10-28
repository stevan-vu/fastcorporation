<?php include(__DIR__ . "/../header.php"); ?>
    <div id="stats-container">
        <h1>Statistics</h1>

        <form method="get" action="/statistics" class="filters">
            <label>
                Date
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
            </label>

            <label>
                User
                <select name="userId">
                    <option value="">All users</option>
                    <?php foreach ($users as $id => $email): ?>
                        <option value="<?= $id ?>" <?= (string) $id === $userId ? 'selected' : '' ?>><?= htmlspecialchars($email) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Action
                <select name="action">
                    <option value="">All actions</option>
                    <?php foreach ($actions as $name): ?>
                        <option value="<?= htmlspecialchars($name) ?>" <?= $name === $action ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit">Filter</button>
            <a href="/statistics">Reset</a>
        </form>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($events === []): ?>
            <p class="hint">No events found.</p>
        <?php else: ?>
            <p class="count"><?= $total ?> event<?= $total === 1 ? '' : 's' ?></p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= $event->getCreatedAt()->format('Y-m-d H:i:s') ?></td>
                                <td><?= $event->getUserEmail() !== null ? htmlspecialchars($event->getUserEmail()) : '<span class="muted">guest / deleted</span>' ?></td>
                                <td><?= htmlspecialchars($event->getAction()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($pages > 1): ?>
                        <?php
                        // Page links keep the current filters
                        $pageUrl = fn(int $n): string => '/statistics?' . htmlspecialchars(http_build_query(array_filter(
                            ['date' => $date, 'userId' => $userId, 'action' => $action, 'page' => $n],
                            fn($v): bool => $v !== '',
                        )));
                        ?>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <nav class="pagination">
                                        <?php if ($page > 1): ?><a href="<?= $pageUrl($page - 1) ?>">&laquo; Previous</a><?php endif; ?>
                                        <span>Page <?= $page ?> of <?= $pages ?></span>
                                        <?php if ($page < $pages): ?><a href="<?= $pageUrl($page + 1) ?>">Next &raquo;</a><?php endif; ?>
                                    </nav>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php include(__DIR__ . "/../footer.php"); ?>
