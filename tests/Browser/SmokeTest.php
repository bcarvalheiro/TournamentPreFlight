<?php

it('loads the home page in a browser', function () {
    visit('/')->assertNoSmoke();
});
