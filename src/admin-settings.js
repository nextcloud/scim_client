/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import App from './components/AdminSettings.vue'

const app = createApp(App)
app.mixin({ methods: { t, n } })
app.mount('#scim-client-admin-settings')
