<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="server-list">
		<NcListItem
			:name="server.name"
			:force-display-actions="true">
			<template #subname>
				{{ server.url }}
			</template>
			<template #actions>
				<NcActionButton :close-after-click="true" @click="syncServer(server)">
					{{ t('scim_client', 'Sync') }}
					<template #icon>
						<SyncIcon :size="20" />
					</template>
				</NcActionButton>
				<NcActionButton :close-after-click="true" @click="showEditModal()">
					{{ t('scim_client', 'Edit') }}
					<template #icon>
						<Pencil :size="20" />
					</template>
				</NcActionButton>
				<NcActionButton icon="icon-delete" :close-after-click="true" @click="showDeleteModal()">
					{{ t('scim_client', 'Delete') }}
					<template #icon>
						<NcLoadingIcon v-if="deleting" :size="20" />
					</template>
				</NcActionButton>
			</template>
		</NcListItem>
		<RegisterServerModal
			:show.sync="showEditDialog"
			:servers="servers"
			:get-all-servers="getAllServers"
			:server="server" />
		<DeleteServerModal
			v-show="showDeleteDialog"
			:server="server"
			:deleting="deleting"
			:delete-server="deleteServer"
			:show.sync="showDeleteDialog" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import Pencil from 'vue-material-design-icons/Pencil.vue'
import SyncIcon from 'vue-material-design-icons/Sync.vue'

import DeleteServerModal from './DeleteServerModal.vue'
import RegisterServerModal from './RegisterServerModal.vue'

export default {
	name: 'ServerListItem',
	components: {
		DeleteServerModal,
		NcActionButton,
		NcListItem,
		NcLoadingIcon,
		Pencil,
		RegisterServerModal,
		SyncIcon,
	},
	props: {
		server: {
			type: Object,
			required: true,
			default: () => {},
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
	},
	data() {
		return {
			deleting: false,
			showDeleteDialog: false,
			showEditDialog: false,
			syncing: false,
		}
	},
	methods: {
		syncServer(server) {
			this.syncing = true

			axios.post(generateUrl(`apps/scim_client/servers/${server.id}/sync`))
				.then(res => {
					if (res.data.success) {
						showSuccess(t('scim_client', 'Sync request successful. This may take a while'))
					} else {
						showError(t('scim_client', 'Sync request failed. Check the logs'))
					}
				})
				.catch(err => {
					console.debug(err)
					showError(t('scim_client', 'Sync request failed. Check the logs'))
				})
				.finally(() => {
					this.syncing = false
				})
		},
		deleteServer(server) {
			this.deleting = true

			confirmPassword().then(() => {
				axios.delete(generateUrl(`apps/scim_client/servers/${server.id}`))
					.then(res => {
						if (res.data.success) {
							showSuccess(t('scim_client', 'Server successfully deleted'))
							this.getAllServers()
						} else {
							showError(t('scim_client', 'Failed to delete server. Check the logs'))
						}
					})
					.catch(err => {
						console.debug(err)
						showError(t('scim_client', 'Failed to delete server. Check the logs'))
					})
					.finally(() => {
						this.deleting = false
						this.showDeleteDialog = false
					})
			}).catch(() => {
				this.deleting = false
				this.showDeleteDialog = false
				showError(t('scim_client', 'Password confirmation failed'))
			})
		},
		showEditModal() {
			this.showEditDialog = true
		},
		showDeleteModal() {
			this.showDeleteDialog = true
		},
	},
}
</script>
