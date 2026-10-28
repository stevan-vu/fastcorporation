<?php include(__DIR__ . "/../header.php"); ?>
    <div id="stats-container">
        <h1>Reports</h1>

        <form method="get" action="/reports" class="filters">
            <label>
                From
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
            </label>

            <label>
                To
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
            </label>

            <button type="submit">Show</button>
            <a href="/reports">Last 30 days</a>
        </form>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (array_sum($totals) === 0): ?>
            <p class="hint">No page views or clicks between <?= htmlspecialchars($from) ?> and <?= htmlspecialchars($to) ?>.</p>
        <?php else: ?>
            <figure class="chart">
                <figcaption>Page views and clicks per day</figcaption>
                <div class="chart-plot">
                    <canvas id="report-chart" role="img" aria-label="Line chart of page views and clicks per day. The table below has the same data."></canvas>
                </div>
            </figure>

            <?php
            // Chart data for the script below: dates oldest first, one series per metric
            $chartData = ['dates' => array_keys($days), 'series' => []];
            foreach ($metrics as $action => $label) {
                $chartData['series'][] = ['label' => $label, 'values' => array_column(array_values($days), $action)];
            }
            ?>
            <script type="application/json" id="report-data"><?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php foreach ($metrics as $label): ?>
                                <th><?= htmlspecialchars($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($days, true) as $day => $counts): ?>
                            <tr>
                                <td><?= $day ?></td>
                                <?php foreach ($counts as $action => $count): ?>
                                    <td class="num">
                                        <?php if ($count > 0): ?>
                                            <a href="/statistics?<?= htmlspecialchars(http_build_query(['date' => $day, 'action' => $action])) ?>"><?= $count ?></a>
                                        <?php else: ?>
                                            <span class="muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <?php foreach ($totals as $total): ?>
                                <th class="num"><?= $total ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <script src="/js/chart.umd.js"></script>
            <script>
            (function () {
                const data = JSON.parse(document.getElementById('report-data').textContent);
                const css = getComputedStyle(document.querySelector('.chart'));
                // Series colors live in styles.css (--series-1..4), in metric order
                const color = (i) => css.getPropertyValue('--series-' + (i + 1)).trim();
                const fmtDate = (iso) => new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
                // Series often have equal counts and their lines overlap, so each one also gets its own marker,
                // and the click series (3 and 4) are dashed and drawn on top of the page views
                const markers = ['circle', 'triangle', 'rect', 'rectRot'];
                const isClick = (i) => i >= 2;
                new Chart(document.getElementById('report-chart'), {
                    type: 'line',
                    data: {
                        labels: data.dates.map(fmtDate),
                        datasets: data.series.map((s, i) => ({
                            label: s.label,
                            data: s.values,
                            borderColor: color(i),
                            backgroundColor: color(i),
                            borderWidth: 2,
                            borderDash: isClick(i) ? [6, 4] : [],
                            order: isClick(i) ? 0 : 1,
                            pointStyle: markers[i],
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        // Hovering anywhere on a day shows all four counts for that day
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', align: 'start', labels: { usePointStyle: true, boxHeight: 8 } },
                            tooltip: { usePointStyle: true },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 16 } },
                            // Counts are whole numbers
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            })();
            </script>
        <?php endif; ?>
    </div>
<?php include(__DIR__ . "/../footer.php"); ?>
