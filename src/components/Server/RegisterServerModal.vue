<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal
		:name="modalName"
		:show="show"
		@close="closeModal">
		<div class="register-server-modal">
			<h2 class="center">
				{{ modalName }}
			</h2>
			<div class="form-group" :aria-label="t('scim_client', 'Name')">
				<label for="server-name">{{ t('scim_client', 'Name') }}</label>
				<NcInputField
					id="server-name"
					:value.sync="serverName"
					:placeholder="t('scim_client', 'Server Nickname')"
					:aria-label="t('scim_client', 'Server Nickname')"
					:error="!isServerNameUnique"
					:helper-text="serverNameFieldHelperText" />
			</div>
			<div class="form-group" :aria-label="t('scim_client', 'Server URL')">
				<label for="server-url">{{ t('scim_client', 'Server URL') }}</label>
				<NcInputField
					id="server-url"
					:value.sync="serverUrl"
					:placeholder="exampleUrl"
					:aria-label="t('scim_client', 'Server URL')"
					:error="!isServerUrlValid || !isServerUrlUnique"
					:helper-text="serverUrlFieldHelperText" />
			</div>
			<div class="form-group" :aria-label="t('scim_client', 'API Key')">
				<label for="server-api-key">{{ t('scim_client', 'API Key') }}</label>
				<NcPasswordField
					id="server-api-key"
					:as-text="true"
					:show-trailing-button="false"
					:value.sync="serverApiKey"
					:placeholder="t('scim_client', 'Server API Key')"
					:aria-label="t('scim_client', 'Server API Key')" />
			</div>
			<div class="row">
				<NcButton
					type="primary"
					:disabled="!isFormValidated"
					@click="isEdit ? updateServer() : registerServer()">
					{{ isEdit ? t('scim_client', 'Save') : t('scim_client', 'Register') }}
					<template #icon>
						<Check v-if="!registeringServer" :size="20" />
						<NcLoadingIcon v-else :size="20" />
					</template>
				</NcButton>
				<NcButton
					type="secondary"
					:disabled="!isServerDetailsValidated"
					@click="checkServerConnection()">
					{{ t('scim_client', 'Check connection') }}
					<template #icon>
						<Connection v-if="!checkingServerConnection" :size="20" />
						<NcLoadingIcon v-else :size="20" />
					</template>
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'

import Check from 'vue-material-design-icons/Check.vue'
import Connection from 'vue-material-design-icons/Connection.vue'

export default {
	name: 'RegisterServerModal',
	components: {
		Check,
		Connection,
		NcButton,
		NcInputField,
		NcLoadingIcon,
		NcModal,
		NcPasswordField,
	},
	props: {
		show: {
			type: Boolean,
			required: true,
			default: false,
		},
		servers: {
			type: Array,
			required: true,
			default: () => [],
		},
		getAllServers: {
			type: Function,
			required: true,
		},
		server: {
			type: Object,
			required: false,
			default: () => null,
		},
	},
	data() {
		return {
			checkingServerConnection: false,
			exampleUrl: 'https://api.example.com/scim/v2',
			isEdit: this.server !== null,
			registeringServer: false,
			serverName: this.server?.name ?? '',
			serverUrl: this.server?.url ?? '',
			serverApiKey: this.server?.api_key ?? '',
		}
	},
	computed: {
		modalName() {
			return this.isEdit ? t('scim_client', 'Edit Server Details') : t('scim_client', 'Register New Server')
		},
		isServerNameUnique() {
			return !this.servers.length || !this.servers.some(server => server.name === this.serverName.trim() && server.name !== this.server?.name)
		},
		serverNameFieldHelperText() {
			return this.isServerNameUnique ? '' : t('scim_client', 'A server with this name already exists')
		},
		isServerUrlValid() {
			return !this.serverUrl.length || /^https?:\/\//.test(this.serverUrl)
		},
		isServerUrlUnique() {
			if (!this.servers.length) {
				return true
			}

			const endSlashes = /\/+$/g
			const newServerUrl = this.serverUrl.replace(endSlashes, '')
			return !this.servers.some((server) => server.url === newServerUrl && server.url !== this.server?.url)
		},
		serverUrlFieldHelperText() {
			if (this.serverUrl.length && !this.isServerUrlValid) {
				return t('scim_client', 'URL should start with http:// or https://')
			}

			if (!this.isServerUrlUnique) {
				return t('scim_client', 'A server with this URL already exists')
			}

			return ''
		},
		isServerDetailsValidated() {
			return this.serverUrl.length && this.isServerUrlValid && this.isServerUrlUnique && this.serverApiKey.length
		},
		isFormValidated() {
			return this.serverName.trim().length && this.isServerNameUnique && this.isServerDetailsValidated
		},
	},
	watch: {
		show(newShow) {
			if (newShow) {
				this.resetData()
			}
		},
	},
	methods: {
		resetData() {
			Object.assign(this.$data, this.$options.data.apply(this))
		},
		checkServerConnection() {
			this.checkingServerConnection = true

			axios.post(generateUrl('apps/scim_client/servers/verify'), { server: this._buildServerParams() })
				.then(res => {
					if (res.data.success) {
						showSuccess(t('scim_client', 'Server connection successful'))
					} else {
						showError(t('scim_client', 'Failed to connect to server. Check the logs'))
					}
				})
				.catch(err => {
					console.debug(err)
					showError(t('scim_client', 'Failed to check connection to server. Check the logs'))
				})
				.finally(() => {
					this.checkingServerConnection = false
				})
		},
		registerServer() {
			this.registeringServer = true

			confirmPassword().then(() => {
				axios.post(generateUrl('apps/scim_client/servers'), { params: this._buildServerParams() })
					.then(res => {
						if (res.data.success) {
							showSuccess(t('scim_client', 'Server successfully registered, initial sync started'))
							this.closeModal()
							this.getAllServers()
						} else {
							showError(t('scim_client', 'Failed to register server. Check the logs'))
						}
					})
					.catch(err => {
						console.debug(err)
						showError(t('scim_client', 'Failed to register server. Check the logs'))
					})
					.finally(() => {
						this.registeringServer = false
					})
			}).catch(() => {
				this.registeringServer = false
				showError(t('scim_client', 'Password confirmation failed'))
			})
		},
		updateServer() {
			if (!this.isEdit) {
				console.debug('Logic error: cannot update server if unset')
				return
			}

			this.registeringServer = true

			confirmPassword().then(() => {
				axios.put(generateUrl(`apps/scim_client/servers/${this.server.id}`), { params: this._buildServerParams() })
					.then(res => {
						if (res.data.success) {
							showSuccess(t('scim_client', 'Server successfully updated'))
							this.closeModal()
							this.getAllServers()
						} else {
							showError(t('scim_client', 'Failed to update server. Check the logs'))
						}
					})
					.catch(err => {
						console.debug(err)
						showError(t('scim_client', 'Failed to update server. Check the logs'))
					})
					.finally(() => {
						this.registeringServer = false
					})
			}).catch(() => {
				this.registeringServer = false
				showError(t('scim_client', 'Password confirmation failed'))
			})
		},
		_buildServerParams() {
			return {
				name: this.serverName.trim(),
				url: this.serverUrl,
				api_key: this.serverApiKey,
			}
		},
		closeModal() {
			this.$emit('update:show', false)
		},
	},
}
</script>

<style scoped lang="scss">
.register-server-modal{
	margin: 20px;

	.form-group {
		margin-bottom: 10px;
		width: 100%;
	}

	.row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 20px;
	}
}
</style>
