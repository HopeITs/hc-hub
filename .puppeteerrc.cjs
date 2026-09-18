/**
 * Browsershot drives the system Chrome (see config/browsershot.php,
 * BROWSERSHOT_CHROME_PATH) rather than a bundled one — skip Puppeteer's own
 * multi-hundred-MB Chromium download on install.
 */
module.exports = {
    skipDownload: true,
};
