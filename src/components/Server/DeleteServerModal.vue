<template>
	<NcModal
		:name="modalName"
		:show="show"
		@close="closeModal">
		<div class="delete-server-modal">
			<h3>{{ t('scim_client', 'Are you sure you want to delete this server?') }}</h3>

			<div class="actions">
				<NcButton type="tertiary" @click="closeModal">
					<template #icon>
						<Cancel :size="20" />
					</template>
					{{ t('scim_client', 'Cancel') }}
				</NcButton>
				<NcButton type="error"
					:disabled="deleting"
					@click="deleteServer(server)">
					<template #icon>
						<NcLoadingIcon v-if="deleting" :size="20" />
						<Delete v-else :size="20" />
					</template>
					{{ t('scim_client', 'Delete') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'

import Cancel from 'vue-material-design-icons/Cancel.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'DeleteServerModal',
	components: {
		Cancel,
		Delete,
		NcButton,
		NcLoadingIcon,
		NcModal,
	},
	props: {
		show: {
			type: Boolean,
			required: true,
			default: false,
		},
		deleteServer: {
			type: Function,
			required: true,
		},
		deleting: {
			type: Boolean,
			required: true,
			default: false,
		},
		server: {
			type: Object,
			required: true,
			default: () => {},
		},
	},
	data() {
		return {
			modalName: t('scim_client', 'Delete Server Confirmation'),
		}
	},
	methods: {
		closeModal() {
			this.$emit('update:show', false)
		},
	},
}
</script>

<style scoped lang="scss">
.delete-server-modal {
	margin: 20px;

	.actions {
		display: flex;
		justify-content: flex-end;

		button {
			margin-left: 10px;
		}
	}
}
</style>
