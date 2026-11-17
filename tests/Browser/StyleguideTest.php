<?php

use Pest\Browser\Api\AwaitableWebpage;

/*
 * The styleguide shows every shared component. These tests open it in a real
 * browser (Chromium via Playwright) at phone and desktop width.
 *
 * At phone width the ResponsiveDialog is a bottom drawer (vaul); at desktop
 * width it is a centred dialog (Radix). Both must behave the same way.
 * The 'widths' and 'dialog variants' datasets live in tests/Browser/Datasets.php.
 */

beforeEach(function () {
    registerWebRoutesIn('local');
});

/**
 * Open the styleguide at the given window size.
 */
function visitStyleguide(int $width, int $height): AwaitableWebpage
{
    return visit('/styleguide')->resize($width, $height);
}

/**
 * Wait until the dialog has finished its closing animation and left the page.
 * The browser keeps checking every 20ms; Playwright's own timeout stops it if it never closes.
 */
function waitUntilDialogIsGone(AwaitableWebpage $page): void
{
    $page->script(<<<'JS'
        () => new Promise((resolve) => {
            const timer = setInterval(() => {
                if (! document.querySelector('[role="dialog"]')) {
                    clearInterval(timer);
                    resolve(true);
                }
            }, 20);
        })
        JS);
}

it('renders every component without horizontal scroll', function (int $width, int $height) {
    $page = visitStyleguide($width, $height);

    $page->assertSee('App logo')
        ->assertSee('Tournament types')
        ->assertSee('Buttons')
        ->assertSee('Responsive dialog')
        ->assertSee('Toast')
        ->assertSee('Empty state')
        ->assertSee('Not affiliated with')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
})->with('widths');

it('closes the dialog on Escape and returns focus to the button that opened it', function (int $width, int $height, string $expectedDialog) {
    $page = visitStyleguide($width, $height)->click('@open-dialog');
    $page->assertVisible('[role="dialog"]')
        ->assertPresent($expectedDialog);

    $page->keys('[role="dialog"]', 'Escape');

    waitUntilDialogIsGone($page);
    $page->assertNotPresent('[role="dialog"]')
        ->assertScript("document.activeElement.dataset.testid === 'open-dialog'")
        ->assertNoJavaScriptErrors();
})->with('dialog variants');

it('closes the dialog when the backdrop is clicked', function (int $width, int $height, string $expectedDialog) {
    $page = visitStyleguide($width, $height)->click('@open-dialog');
    $page->assertVisible('[role="dialog"]')
        ->assertPresent($expectedDialog);

    // Pest's click() aims at the centre of an element, and the dialog covers the
    // backdrop's centre. So use Playwright directly: a real mouse click 5px from
    // the backdrop's top-left corner, where nothing covers it. Playwright refuses
    // to click if another element is on top, so this also proves it is the backdrop.
    $page->page()
        ->locator('[data-slot="dialog-overlay"], [data-slot="drawer-overlay"]')
        ->click(['position' => ['x' => 5, 'y' => 5]]);

    waitUntilDialogIsGone($page);
    $page->assertNotPresent('[role="dialog"]')
        ->assertNoJavaScriptErrors();
})->with('dialog variants');

it('keeps keyboard focus inside the open dialog', function (int $width, int $height, string $expectedDialog) {
    $page = visitStyleguide($width, $height)->click('@open-dialog');
    $page->assertVisible('[role="dialog"]')
        ->assertPresent($expectedDialog);

    foreach (range(1, 6) as $press) {
        $page->keys(':focus', 'Tab');

        $page->assertScript('document.querySelector(\'[role="dialog"]\').contains(document.activeElement)');
    }

    $page->assertNoJavaScriptErrors();
})->with('dialog variants');
