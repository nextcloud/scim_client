<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal
		:name="modalName"
		:show="show"
		@close="closeModal">
		<div class="delete-server-modal">
			<h3>{{ t('scim_client', 'Are you sure you want to delete this server?') }}</h3>

			<div class="actions">
				<NcButton variant="tertiary" @click="closeModal">
					{{ t('scim_client', 'Cancel') }}
				</NcButton>
				<NcButton variant="error"
					:disabled="deleting"
					@click="deleteServer(server)">
					<template #icon>
						<NcLoadingIcon v-if="deleting" :size="20" />
						<TrashCanOutlineIcon v-else :size="20" />
					</template>
					{{ t('scim_client', 'Delete') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcModal from '@nextcloud/vue/components/NcModal'

import TrashCanOutlineIcon from 'vue-material-design-icons/TrashCanOutline.vue'

export default {
	name: 'DeleteServerModal',
	components: {
		NcButton,
		NcLoadingIcon,
		NcModal,
		TrashCanOutlineIcon,
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
