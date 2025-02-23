/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

const isProduction = process.env.NODE_ENV === 'production'

export default createAppConfig(
	{
		'admin-settings': resolve(join('src', 'admin-settings.js')),
	},
	{
		createEmptyCSSEntryPoints: true,
		extractLicenseInformation: true,
		thirdPartyLicense: false,
		inlineCSS: { relativeCSSInjection: true },
		minify: isProduction,
	}
)
