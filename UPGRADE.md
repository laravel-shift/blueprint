# Upgrade Guide

## Upgrading from 2.x to 2.6
Version 2.6 was a minor release to add support for Laravel 10. This adopts the latest conventions from Laravel, most notably the addition of type-hints in code.

The only other change is the removal of `use_return_types` configuration, as this is now Blueprint's default behavior.

Inline with Blueprint's [Support Policy](https://github.com/laravel-shift/blueprint#support-policy), this release also drops support for Laravel 9. If you are still running Laravel 9, you may constrain your Blueprint version to `2.5.0`.

## Upgrading from 1.x to 2.x
Version 2.x was a superficial major release to reflect Blueprint's new [Support Policy](https://github.com/laravel-shift/blueprint#support-policy). There were no changes to the underlying grammar. A few configuration options and methods were changed. Additional notes are below. You may view the full set of changes in [#496](https://github.com/laravel-shift/blueprint/pull/496).

### Configuration changes
The following configuration options were changed to reflect new conventions in Laravel 8.

- `models_namespace` default value is now `Models`.
- `generate_fqcn_route` was removed. Blueprint now generates all routes using fully qualified class names and tuples.

### Removed methods
The following static methods on the `Blueprint` class were changed:

- `supportsReturnTypeHits` was renamed to `useReturnTypeHints`.
- `isLaravel8OrHigher` was removed.
- `isPHP7OrHigher` was removed.
