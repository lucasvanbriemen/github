<script>
	let { isOpen = false, onClose, onConfirm, title = 'Confirm Action', message = 'Are you sure you want to proceed?', confirmText = 'Confirm', cancelText = 'Cancel'} = $props();

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
			<p>{message}</p>
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
