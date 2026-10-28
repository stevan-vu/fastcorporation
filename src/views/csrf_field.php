<input type="hidden" name="<?= App\Security\Csrf::FIELD ?>" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
