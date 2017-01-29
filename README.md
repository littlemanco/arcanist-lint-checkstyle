# Arcanist Checkstyle Lint

## Project Goals 

Arcanist provides an excellent linting system, that aggregates other linting tools for easy consumption. This makes
tools that output content in the checkstyle format consumable by arcanist.

## Similar Work

This was largely based off the script-and-regex check that already existed

## Justification

I could not figure out a way to get checkstyle content into arcanist easily otherwise; Checkstyle is XML, so it was easy
and interesting to build a parser.

## Limitations

- Only tested with shellcheck


## Summary

- License: MIT
- Code Style: PSR2*
- Locale: en-AU [lang]_

* Amusingly, it fails PSR2

## Compatibility

### PHP

- 5.6 (probably)
- 7

## Installation

This tool can be installed with composer:

```
$ composer require-dev littlemanco/arcanist-lint-checkstyle
```

It's available on Packagist, so it should "Just Work"

## Usage

This linter integrates with the normal Arcanist lints, however some additional setup is required to get Arcanist to find
the linter. Create a file in your repo as follows:

```
{
  "load": [
    "vendor/littlemanco/arcanist-lint-checkstyle/src/"
  ]
}
```

This allows arcanist to discover the lint. Then, it can be configured like other lints:

```
{
  "linters": {
    ...
    "sh": {
      "type": "checkstyle",
      "include": "(\\.sh$)",
      "checkstyle.script": "sh -c 'shellcheck --format=checkstyle \"$0\" || true'"
    }
    ...
  }
}

```

## Thanks

- Phabricator
- Checkstyle
- Shellcheck

## Contributing

Contributions are always welcome! Nothing is too small, and the best place to start is to open an issue.

## References

.. [lang] Lingoes.net,. (2015). Language Code Table. Retrieved 4 June 2015, from http://www.lingoes.net/en/translator/langcode.htm