<?php

arch()->preset()->php();

arch()->preset()->security();

arch('no debugging functions')
    ->expect(['dd', 'dump', 'ray', 'ddd', 'var_dump'])
    ->not->toBeUsed();
