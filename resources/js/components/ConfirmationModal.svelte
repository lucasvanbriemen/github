<script>
	import { onMount } from 'svelte';

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

	$effect(() => {
		if (isOpen) {
			document.body.style.overflow = 'hidden';
		} else {
			document.body.style.overflow = '';
		}
	});
</script>

{#if isOpen}
	<div class="modal-backdrop" onclick={handleBackdropClick}>
		<div class="confirmation-modal">
			<div class="modal-header">
				<h2 class="modal-title">{title}</h2>
			</div>
			<div class="modal-body">
				<p>{message}</p>
			</div>
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
