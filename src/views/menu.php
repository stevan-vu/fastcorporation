<?php
// Each item uses the same permission as its route in routes.php, so users only see links they can open
$menuItems = [
    ['path' => '/page-a',     'label' => 'Page A',     'permission' => 'view_pages'],
    ['path' => '/page-b',     'label' => 'Page B',     'permission' => 'view_pages'],
    ['path' => '/statistics', 'label' => 'Statistics', 'permission' => 'view_pages_with_stats'],
    ['path' => '/reports',    'label' => 'Reports',    'permission' => 'view_pages_with_stats'],
];

// Pages on the left, account links (login/register/logout) on the right
$left = [];
$right = [];
if (isset($auth) && $auth->check()) {
    foreach ($menuItems as $item) {
        if ($auth->can($item['permission'])) {
            $left[] = '<a href="' . $item['path'] . '">' . htmlspecialchars($item['label']) . '</a>';
        }
    }
    // POST so a link or prefetch elsewhere can't log the user out
    $right[] = '<form method="post" action="/logout" class="logout-form">'
        . '<input type="hidden" name="' . App\Security\Csrf::FIELD . '" value="' . htmlspecialchars($csrfToken ?? '') . '">'
        . '<button type="submit">Logout</button></form>';
} else {
    $right[] = '<a href="/login">Login</a>';
    $right[] = '<a href="/register">Register</a>';
}
?>
<nav class="menu">
    <span><?= implode(' | ', $left) ?></span>
    <span><?= implode(' | ', $right) ?></span>
</nav>
