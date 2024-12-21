<template>
	<div class="server-list">
		<NcListItem
			:name="server.name"
			:force-display-actions="true">
			<template #subname>
				{{ server.url }}
			</template>
			<template #actions>
				<NcActionButton :close-after-click="true" @click="showSyncModal()">
					{{ t('scim_client', 'Sync') }}
					<template #icon>
						<Sync :size="20" />
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
		<!--
		<SyncServerModal
			v-show="showSyncDialog"
			:server="server"
			:syncing="syncing"
			:sync-server="syncServer"
			:show.sync="showSyncDialog" />
		-->
		<RegisterServerModal
			:show.sync="showEditDialog"
			:servers="servers"
			:get-all-servers="getAllServers"
			:server="server" />
		<!--
		<DeleteServerModal
			v-show="showDeleteDialog"
			:server="server"
			:deleting="deleting"
			:delete-server="deleteServer"
			:show.sync="showDeleteDialog" />
		-->
	</div>
</template>

<script>
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import Pencil from 'vue-material-design-icons/Pencil.vue'
import Sync from 'vue-material-design-icons/Sync.vue'

// import DeleteServerModal from './DeleteServerModal.vue'
import RegisterServerModal from './RegisterServerModal.vue'
// import SyncServerModal from './SyncServerModal.vue'

export default {
	name: 'ServerListItem',
	components: {
		// DeleteServerModal,
		NcActionButton,
		NcListItem,
		NcLoadingIcon,
		Pencil,
		RegisterServerModal,
		Sync,
		// SyncServerModal,
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
			showSyncDialog: false,
			syncing: false,
		}
	},
	methods: {
		syncServer(server) {
			this.syncing = true

			// TODO: manually sync server

			this.syncing = false
		},
		deleteServer(server) {
			this.deleting = true

			// TODO: delete server from database

			this.deleting = false
		},
		showSyncModal() {
			this.showSyncDialog = true
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
