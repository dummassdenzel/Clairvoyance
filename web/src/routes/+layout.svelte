<script lang="ts">
	import '../app.css';
	import { onMount } from 'svelte';
	import { verifySession, authStore } from '$lib/services/auth';
	import Navbar from '$lib/components/Navbar.svelte';
	import { navigating } from '$app/stores';
	
	let isLoading = true;
	
	onMount(async () => {
		// Check if token exists and is valid on app initialization
		await verifySession();
		isLoading = false;
	});
</script>

<div class="app-container">
	{#if isLoading}
		<div class="loading-overlay">
			<div class="loading-spinner">Loading...</div>
		</div>
	{:else}
		
		<main class="main-content">
			{#if $navigating}
				<div class="page-transition"></div>
			{/if}
			
			<slot />
		</main>
		
		<footer class="app-footer">
			<div class="footer-content">
				<p>&copy; {new Date().getFullYear()} Clairvoyance KPI Dashboard</p>
			</div>
		</footer>
	{/if}
</div>

<style>
	.app-container {
		display: flex;
		flex-direction: column;
		min-height: 100vh;
	}
	
	.loading-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		justify-content: center;
		align-items: center;
		background-color: rgba(255, 255, 255, 0.8);
		z-index: 1000;
	}
	
	.loading-spinner {
		color: #4a90e2;
		font-weight: bold;
	}
	
	.main-content {
		flex: 1;
		position: relative;
	}
	
	.page-transition {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 3px;
		background: linear-gradient(to right, #4a90e2, #3cba92);
		animation: loadingAnimation 1s infinite linear;
		z-index: 10;
	}
	
	@keyframes loadingAnimation {
		0% {
			transform: translateX(-100%);
		}
		100% {
			transform: translateX(100%);
		}
	}
	
	.app-footer {
		background-color: #f8f9fa;
		padding: 1.5rem;
		border-top: 1px solid #e9ecef;
		margin-top: 2rem;
	}
	
	.footer-content {
		max-width: 1200px;
		margin: 0 auto;
		text-align: center;
		color: #6c757d;
	}
</style>
