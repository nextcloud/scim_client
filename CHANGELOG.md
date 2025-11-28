<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-11-28

### Added

- Support for SCIM servers without bulk operations enabled. #109

### Fixed

- Parameter name for new server verification requests. #108

## [1.0.6] - 2025-11-21

### Fixed

- Server sync failure due to missing server properties. #95
- Incorrect JSON for bulk sync group member operations. #102
- Use saved API key when verifying existing server. #105

### Changed

- Update npm packages. #82 #83 #84 #85 #91 #92 #96 #98 #100 #104 #106
- Update GitHub Actions workflows. #86 #94 #99 #103
- Bump maximum supported Nextcloud version to 33. #102

## [1.0.5] - 2025-07-02

### Changed

- Switch to outlined icons. #78
- Update npm packages. #72 #73 #74 #77
- Update GitHub Actions workflows. #79
- Update php-cs-fixer dependencies. #75

## [1.0.4] - 2025-05-21

### Fixed

- Composer scripts for app store publish workflow. #71

## [1.0.3] - 2025-05-21

### Added

- REUSE compliance. #37

### Changed

- Update npm packages. #31 #32 #34 #35 #53 #55 #58 #61 #62 #67 #69
- Update Psalm CI tests. #59
- Update GitHub Actions workflows. #43 #63
- Update Dependabot. #60 #64

### Removed

- OpenAPI specification and extractor. #66

## [1.0.2] - 2025-01-31

### Fixed

- App description. #28

## [1.0.1] - 2025-01-29

### Fixed

- App store publish workflow. #25

## [1.0.0] - 2025-01-29

### Added

- Initial app release.

[Unreleased]: https://github.com/nextcloud/scim_client/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/nextcloud/scim_client/compare/v1.0.6...v1.1.0
[1.0.6]: https://github.com/nextcloud/scim_client/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/nextcloud/scim_client/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/nextcloud/scim_client/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/nextcloud/scim_client/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/nextcloud/scim_client/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/nextcloud/scim_client/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/nextcloud/scim_client/releases/tag/v1.0.0
