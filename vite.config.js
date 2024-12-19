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
