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
			<NcButton
				type="primary"
				@click="isEdit ? updateServer() : registerServer()">
				{{ isEdit ? t('scim_client', 'Save') : t('scim_client', 'Register') }}
				<template #icon>
					<Check v-if="!registeringServer" :size="20" />
					<NcLoadingIcon v-else :size="20" />
				</template>
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'

import Check from 'vue-material-design-icons/Check.vue'

export default {
	name: 'RegisterServerModal',
	components: {
		Check,
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
			exampleUrl: 'https://api.example.com/scim/v2',
			isEdit: this.server !== null,
			registeringServer: false,
			serverName: this.server?.name ?? '',
			serverUrl: this.server?.url ?? '',
			serverApiKey: this.server?.apiKey ?? '',
		}
	},
	computed: {
		modalName() {
			return this.isEdit ? t('scim_client', 'Edit Server Details') : t('scim_client', 'Register New Server')
		},
		isServerNameUnique() {
			return !this.servers.length || !this.servers.some(server => server.name === this.serverName && server.name !== this.server?.name)
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
			const newServerName = this.serverName.replace(endSlashes, '')
			const currentServerName = this.server?.name.replace(endSlashes, '')

			return this.servers.some((server) => {
				const listServerName = server.name.replace(endSlashes, '')
				return listServerName === newServerName && listServerName !== currentServerName
			})
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
		registerServer() {
			this.registeringServer = true

			// TODO: add server details to database

			this.registeringServer = false
		},
		updateServer() {
			this.registeringServer = true

			// TODO: update server details in database

			this.registeringServer = false
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

	button {
		margin-top: 20px;
	}
}
</style>
