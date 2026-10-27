<?php

use Edalzell\Features\ClassFinder;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\Configs\MailWriter;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\EnvWriter;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\Helper;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\Tagged;
use Edalzell\Features\Tests\Fixtures\Discovery\Setup\Writer;

it('lists every class the files declare, nested ones included, sorted by path', function () {
    $classes = ClassFinder::classes(fixturePath('Discovery/src/Setup'));

    expect($classes->all())->toBe([MailWriter::class, EnvWriter::class, Helper::class, Writer::class]);
});

it('keeps only concrete subclasses of a base', function () {
    $classes = ClassFinder::classes(fixturePath('Discovery/src/Setup'), Writer::class);

    expect($classes->all())->toBe([MailWriter::class, EnvWriter::class]);
});

it('stops at a depth, so nothing below it is read or autoloaded', function () {
    $path = fixturePath('Discovery/src/Setup');

    expect(ClassFinder::classes($path, depth: 0)->all())->toBe([EnvWriter::class, Helper::class, Writer::class])
        ->and(ClassFinder::classes($path, Writer::class, depth: 0)->all())->toBe([EnvWriter::class])
        ->and(ClassFinder::classes($path, Writer::class, depth: 1)->all())->toBe([MailWriter::class, EnvWriter::class]);
});

it('accepts an interface as the base', function () {
    $classes = ClassFinder::classes(fixturePath('Discovery/src/Setup'), Tagged::class);

    expect($classes->all())->toBe([EnvWriter::class]);
});

it('finds nothing in a missing directory', function () {
    expect(ClassFinder::classes(fixturePath('Discovery/src/Missing')))->toBeEmpty();
});

it('finds the class past comments, braces, enums and anonymous classes', function () {
    $spaced = '<?php namespace Features\\TwoWords { class /* spaced */ Spaced {} }';
    $enum = '<?php namespace Features\\TwoWords; enum Shape { case Square; public function anon() { return new class(1) extends \\stdClass {}; } }';
    $constant = '<?php namespace Features\\TwoWords; #[Attr(Command::class)] interface Marker {}';
    $global = '<?php class Bare {}';

    $disk = mockOnDemandDisk();
    $disk->put('src/Things/Spaced.php', $spaced);
    $disk->put('src/Things/Shape.php', $enum);
    $disk->put('src/Things/Marker.php', $constant);
    $disk->put('src/Things/Bare.php', $global);

    expect(ClassFinder::classes(featureFile('src/Things'))->all())->toBe(['Bare', 'Features\\TwoWords\\Spaced'])
        ->and(ClassFinder::className(featureFile('src/Things/Shape.php')))->toBeNull();
});
