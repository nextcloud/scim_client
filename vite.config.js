/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import eslint from 'vite-plugin-eslint'
import stylelint from 'vite-plugin-stylelint'
import { join, resolve } from 'path'

const isProduction = process.env.NODE_ENV === 'production'

export default createAppConfig(
	{
		'admin-settings': resolve(join('src', 'admin-settings.js')),
	},
	{
		config: {
			css: {
				modules: {
					localsConvention: 'camelCase',
				},
				preprocessorOptions: {
					scss: {
						api: 'modern-compiler',
					},
				},
			},
			plugins: [eslint(), stylelint()],
			build: {
				cssCodeSplit: true,
			},
		},
		createEmptyCSSEntryPoints: true,
		extractLicenseInformation: true,
		thirdPartyLicense: false,
		inlineCSS: { relativeCSSInjection: true },
		minify: isProduction,
	}
)
