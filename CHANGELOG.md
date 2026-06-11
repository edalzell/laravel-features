# Changelog

## v0.5.1 - 2026-06-11

### 🐛 Fixed

- Don’t use helper for composer hooks [@edalzell](https://github.com/edalzell) (#56)
- Fix package feature loading [@edalzell](https://github.com/edalzell) (#57)
- Fix stub namespace [@edalzell](https://github.com/edalzell) (#55)

### 🧰 Maintenance

- Bump release-drafter/release-drafter from 7.3.0 to 7.3.1 [@[dependabot[bot]](https://github.com/apps/dependabot)](https://github.com/[dependabot[bot]](https://github.com/apps/dependabot)) (#49)
  
- Bump cardinalby/git-get-release-action from 1.2.5 to 1.2.6 [@[dependabot[bot]](https://github.com/apps/dependabot)](https://github.com/[dependabot[bot]](https://github.com/apps/dependabot)) (#50)
  
- Bump actions/checkout from 6.0.2 to 6.0.3 [@[dependabot[bot]](https://github.com/apps/dependabot)](https://github.com/[dependabot[bot]](https://github.com/apps/dependabot)) (#51)
  
- Ensure whole test suite can be run [@edalzell](https://github.com/edalzell) (#48)
  

## v0.5.0 - 2026-05-24

### 🚀 New

- Auto-register policies [@edalzell](https://github.com/edalzell) (#39)

### 🧰 Maintenance

- Simplify namespace() by removing no-op [@edalzell](https://github.com/edalzell) (#47)
- Replace Storage disk with File::directories() [@edalzell](https://github.com/edalzell) (#46)
- Simplify seeders singleton [@edalzell](https://github.com/edalzell) (#45)
- Don't add composer hook unless it's needed [@edalzell](https://github.com/edalzell) (#44)
- Improve namespaces error handling [@edalzell](https://github.com/edalzell) (#43)
- Replace placeholder description in composer.json [@edalzell](https://github.com/edalzell) (#42)
- Fix bootConfig() publishing a relative source path [@edalzell](https://github.com/edalzell) (#41)
- Document the global side-effect of guessClassNamesUsing() [@edalzell](https://github.com/edalzell) (#40)
- Bump shivammathur/setup-php from 2.37.0 to 2.37.1 [@[dependabot[bot]](https://github.com/apps/dependabot)](https://github.com/[dependabot[bot]](https://github.com/apps/dependabot)) (#37)
- Update Get Release action [@edalzell](https://github.com/edalzell) (#38)

## v0.4.0 - 2026-05-17

### 🚀 New

- Support package features [@edalzell](https://github.com/edalzell) (#26)
- Drop Laravel 11, add Laravel 13 support [@edalzell](https://github.com/edalzell) (#36)
- Support Windows [@edalzell](https://github.com/edalzell) (#35)

### 🧰 Maintenance

- Update GitHub Action Versions [@edalzell](https://github.com/edalzell) (#33)

## v0.3.1 - 2026-02-17

### 🐛 Fixed

- Fix how seeders are called [@edalzell](https://github.com/edalzell) (https://github.com/edalzell/laravel-features/pull/24)

### 🧰 Maintenance

- Update GitHub Action Versions [@edalzell](https://github.com/edalzell) (#22)

## v0.3.0 - 2026-01-21

### 🚀 New

- Register listeners [@edalzell](https://github.com/edalzell) (#16)

### 🐛 Fixed

- Add test namespaces [@edalzell](https://github.com/edalzell) (#19)
- Improve database classes namespacing [@edalzell](https://github.com/edalzell) (#18)

### 🧰 Maintenance

- Blink is not used anymore [@edalzell](https://github.com/edalzell) (#17)

## v0.2.0 - 2026-01-15

### 🔥 Breaking

- Location of features moved to root [@edalzell](https://github.com/edalzell) (#15)

### 🚀 New

- Seeder support [@edalzell](https://github.com/edalzell) (#14)

### 🐛 Fixed

- Fix database name spacing [@edalzell](https://github.com/edalzell) (#12)

### 🧰 Maintenance

- Refactor [@edalzell](https://github.com/edalzell) (#13)

## v0.1 - 2025-10-25

### 🚀 New

- Initial release!
