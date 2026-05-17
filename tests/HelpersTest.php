<?php

it('returns the package root by removing the last two path segments', function () {
    $path = '/some/folder/site/vendor/edalzell/my-features/features/One';

    expect(packageRoot($path))->toBe('/some/folder/site/vendor/edalzell/my-features');
});

it('returns the package root from a windows-style path', function () {
    $path = 'C:\\some\\folder\\site\\vendor\\edalzell\\my-features\\features\\One';

    expect(packageRoot($path))->toBe('C:/some/folder/site/vendor/edalzell/my-features');
});
