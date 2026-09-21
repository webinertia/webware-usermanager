# webware-usermanager

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-usermanager)](https://packagist.org/packages/webware/webware-usermanager)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-usermanager)](https://packagist.org/packages/webware/webware-usermanager)
[![License](https://img.shields.io/github/license/webinertia/webware-usermanager)](LICENSE)
[![Required CI](https://github.com/webinertia/webware-usermanager/actions/workflows/required/webinertia/.github/.github/workflows/org-required-ci.yml/badge.svg)](https://github.com/webinertia/webware-usermanager/actions/workflows/required/webinertia/.github/.github/workflows/org-required-ci.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-usermanager/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-usermanager)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-usermanager%2F0.1.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-usermanager/0.1.x)

Mezzio user registration, authentication, email verification, and admin user
management for the webware-* package family. Ships with a message-bus driven
command layer, PhpDb-backed user repository, PSR-15 middleware for login and
registration flows, HTMX-aware request handlers, and a webware-admin dashboard
widget.

## Installation

```bash
composer require webware/webware-usermanager
```

### Provider load order

`webware/webware-core` aliases `Mezzio\Authentication\UserInterface` to
`Webware\Core\UserInterface`, and this package registers the implementation behind it.
Register the providers in `config/config.php` in this order:

1. `Mezzio\Authentication\ConfigProvider`
2. `Webware\Core\ConfigProvider`
3. `Webware\UserManager\ConfigProvider`

The component installer adds providers in the order packages were installed, which is not
necessarily this order — check `config/config.php` after installing and reorder if needed.
Any provider that declares an entry for `Mezzio\Authentication\UserInterface` competes for
the same service name and the last one aggregated wins, so load `mezzio/mezzio-authentication`
first, then `webware/webware-core`, then this package.

## Documentation

See [docs/](docs/).
