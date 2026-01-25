<script>
	let { isOpen = false, onClose, onConfirm, title = 'Confirm Action', confirmText = 'Confirm', cancelText = 'Cancel', slot} = $props();

	function handleBackdropClick(e) {
		if (e.target === e.currentTarget) {
			onClose?.();
		}
	}

	function handleConfirm() {
		onConfirm?.();
		onClose?.();
	}
</script>

{#if isOpen}
	<div class="modal-backdrop" onclick={handleBackdropClick}>
		<div class="confirmation-modal">
			<h3 class="modal-title">{title}</h3>
			<slot />
			<div class="modal-actions">
				<button class="button-primary-outline" onclick={onClose}>{cancelText}</button>
				<button class="button-primary" onclick={handleConfirm}>{confirmText}</button>
			</div>
		</div>
	</div>
{/if}

<style lang="scss">
	@import '../../scss/components/confirmation-modal';
</style>
