<script lang="ts">
  import WidgetSettingsModal from '$lib/components/WidgetSettingsModal.svelte';
  import { writable, type Writable } from 'svelte/store';
  import { setContext } from 'svelte';

  const showWidgetSettingsModal = writable(false);
  const selectedWidgetForSettings = writable<any | null>(null);
  const savedWidget = writable<any | null>(null);

  function openWidgetSettings(widget: any) {
    selectedWidgetForSettings.set(widget);
    showWidgetSettingsModal.set(true);
  }

  function handleWidgetSettingsSave(event: CustomEvent) {
    savedWidget.set(event.detail);
  }

  setContext('dashboard-layout', {
    openWidgetSettings,
    savedWidget
  });
</script>

<slot />

<WidgetSettingsModal
  bind:show={$showWidgetSettingsModal}
  widget={$selectedWidgetForSettings}
  on:save={handleWidgetSettingsSave}
  on:close={() => showWidgetSettingsModal.set(false)}
/>
