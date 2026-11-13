<?php

it('loads the home page in a browser', function () {
    // The title check proves our page rendered (an error page has another title);
    // assertNoSmoke() fails on any JavaScript error or console log.
    visit('/')
        ->assertTitleContains(config('app.name'))
        ->assertNoSmoke();
});
