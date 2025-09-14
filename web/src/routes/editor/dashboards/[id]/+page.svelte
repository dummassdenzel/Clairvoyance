<script lang="ts">
  import { page } from '$app/stores';
  import { user } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import { writable, type Writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import type { Dashboard, ApiResponse, Kpi } from '$lib/types';
  import DashboardWidget from '$lib/components/DashboardWidget.svelte';
  
  import ViewersModal from '$lib/components/ViewersModal.svelte';
  import WidgetSettingsModal from '$lib/components/WidgetSettingsModal.svelte';
  import KpiEntriesModal from '$lib/components/KpiEntriesModal.svelte';
  import Grid from 'svelte-grid';
  import gridHelp from 'svelte-grid/build/helper/index.mjs';
  import { getContext } from 'svelte';
  import jsPDF from 'jspdf';
  import html2canvas from 'html2canvas-pro';
  import autoTable from 'jspdf-autotable';

  type Item = {
    id: string;
    x: number;
    y: number;
    w: number;
    h: number;
  } & Record<string, any>;

  const dashboard = writable<Dashboard | null>(null);
  const loading = writable(true);
  const error = writable<string | null>(null);

  let isViewersModalOpen = false;
  let isWidgetSettingsModalOpen = false;
  let isKpiEntriesModalOpen = false;
  let editMode = false;
  let isGeneratingReport = false;
  let newWidgetTemplate: any = null; // Template for new widget
  let selectedKpiForEntries: Kpi | null = null;
  let selectedKpiIdForEntries: number | null = null;

  function handleCancel() {
    editMode = false;
    fetchDashboard();
  }

  const cols: any = [[0, 12]];

  async function handleSave() {
    const layoutToSave = items.map(dataItem => {
      const layout = dataItem[cols[0][1]];
      const { id, title, type, kpi_id, backgroundColor, borderColor, startDate, endDate, aggregation, showLegend, legendPosition } = dataItem;
      return {
        id: Number(id),
        x: layout.x,
        y: layout.y,
        w: layout.w,
        h: layout.h,
        title,
        type,
        kpi_id,
        backgroundColor,
        borderColor,
        startDate,
        endDate,
        aggregation,
        showLegend,
        legendPosition
      };
    });

    try {
      const response: ApiResponse<{ dashboard: Dashboard }> = await api.updateDashboard(parseInt(dashboardId), { 
        layout: JSON.stringify(layoutToSave) 
      });
      
      if (response.success) {
        editMode = false;
        await fetchDashboard();
      } else {
        alert(response.message || 'Failed to save layout.');
      }
    } catch (e) {
      console.error('Failed to save layout:', e);
      alert('Failed to save layout. See console for details.');
    }
  }

  function addWidget() {
    const newWidgetWidth = 4;
    const newWidgetHeight = 7;
    const gridWidth = cols[0][1];

    let nextPos = { x: 0, y: 0 };

    if (items.length > 0) {
        let y = 0;
        let foundPosition = false;
        while (!foundPosition) {
            for (let x = 0; x <= gridWidth - newWidgetWidth; x++) {
                const potentialRect = { x, y, w: newWidgetWidth, h: newWidgetHeight };
                let isOverlapping = items.some(item => {
                    const layout = item[cols[0][1]];
                    const existingRect = { x: layout.x, y: layout.y, w: layout.w, h: layout.h };
                    // Check for overlap
                    return (
                        potentialRect.x < existingRect.x + existingRect.w &&
                        potentialRect.x + potentialRect.w > existingRect.x &&
                        potentialRect.y < existingRect.y + existingRect.h &&
                        potentialRect.y + potentialRect.h > existingRect.y
                    );
                });

                if (!isOverlapping) {
                    nextPos = { x, y };
                    foundPosition = true;
                    break;
                }
            }
            if (!foundPosition) {
                y++;
            }
        }
    }

    const newId = items.length > 0 ? Math.max(...items.map(item => Number(item.id))) + 1 : 0;
    
    // Create a template for the new widget
    newWidgetTemplate = {
      id: String(newId),
      title: 'New Widget',
      type: 'line',
      kpi_id: null,
      backgroundColor: '#36a2eb4d',
      borderColor: '#36a2eb',
      startDate: '',
      endDate: '',
      aggregation: 'latest',
      showLegend: true,
      legendPosition: 'top',
      // Add the layout information
      x: nextPos.x,
      y: nextPos.y,
      w: newWidgetWidth,
      h: newWidgetHeight
    };

    // Open the widget settings modal
    isWidgetSettingsModalOpen = true;
  }

  function handleWidgetSettingsSave(event: CustomEvent) {
    const widgetData = event.detail;
    
    // Create the new widget with the configured data
    const newItem = {
      [cols[0][1]]: gridHelp.item({ 
        x: widgetData.x, 
        y: widgetData.y, 
        w: widgetData.w, 
        h: widgetData.h, 
        fixed: !editMode, 
        min: { w: 2, h: 4 }, 
        customDragger: true 
      }),
      id: widgetData.id,
      title: widgetData.title,
      type: widgetData.type,
      kpi_id: widgetData.kpi_id,
      backgroundColor: widgetData.backgroundColor,
      borderColor: widgetData.borderColor,
      startDate: widgetData.startDate,
      endDate: widgetData.endDate,
      aggregation: widgetData.aggregation,
      showLegend: widgetData.showLegend,
      legendPosition: widgetData.legendPosition
    };
    
    items = [...items, newItem];
    newWidgetTemplate = null;
  }

  function removeWidget(id: string) {
    items = items.filter(item => item.id !== id);
  }

  async function generateReport() {
    isGeneratingReport = true;

    try {
      const response: ApiResponse<any> = await api.getDashboardReport(parseInt(dashboardId));
      
      if (!response.success) {
        throw new Error(response.message || 'Failed to fetch report data.');
      }
      
      const reportData = response.data;
      
      // Check if we have any widgets to report on
      if (!reportData.widgets || reportData.widgets.length === 0) {
        alert('No widgets found in this dashboard to generate a report.');
        return;
      }
      
      const doc = new jsPDF('p', 'mm', 'a4');
      const pageWidth = doc.internal.pageSize.getWidth();
      const pageHeight = doc.internal.pageSize.getHeight();
      const margin = 15;
      const contentWidth = pageWidth - margin * 2;

      // Helper functions
      const addHeader = (title: string, pageNumber?: number) => {
        // Add logo and company name
        try {
          // Load the logo image
          const logoImg = new Image();
          logoImg.src = '/clairvoyance-logo.png';
          
          // Add logo (we'll handle this in the actual rendering)
          doc.setFontSize(10);
          doc.setTextColor(100);
          doc.text(title, margin, 10);
          if (pageNumber) {
            doc.text(`Page ${pageNumber}`, pageWidth - margin, 10, { align: 'right' });
          }
          doc.setDrawColor(200);
          doc.line(margin, 12, pageWidth - margin, 12);
        } catch (e) {
          console.warn('Could not load logo:', e);
          // Fallback without logo
          doc.setFontSize(10);
          doc.setTextColor(100);
          doc.text(title, margin, 10);
          if (pageNumber) {
            doc.text(`Page ${pageNumber}`, pageWidth - margin, 10, { align: 'right' });
          }
          doc.setDrawColor(200);
          doc.line(margin, 12, pageWidth - margin, 12);
        }
      };

      const addFooter = (pageNumber: number) => {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(150);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, margin, pageHeight - 10);
        doc.text(`Dashboard Report - ${reportData.name}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
        doc.text(`Page ${pageNumber}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
      };

      // --- Enhanced Cover Page with Logo ---
      // Add logo and company branding
      try {
        const logoImg = new Image();
        logoImg.src = '/clairvoyance-logo.png';
        
        // Wait for logo to load
        await new Promise((resolve) => {
          if (logoImg.complete) {
            resolve(true);
          } else {
            logoImg.onload = () => resolve(true);
            logoImg.onerror = () => resolve(false);
          }
        });

        // Add logo (20mm height, maintain aspect ratio)
        const logoHeight = 20;
        const logoWidth = (logoImg.width / logoImg.height) * logoHeight;
        const logoX = margin;
        const logoY = 20;
        
        doc.addImage(logoImg.src, 'PNG', logoX, logoY, logoWidth, logoHeight);
        
        // Add company name next to logo
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.setTextColor(31, 41, 55);
        doc.text('Clairvoyance', logoX + logoWidth, logoY + logoHeight/2 + 2);
        
      } catch (e) {
        console.warn('Could not add logo to cover page:', e);
        // Fallback: just add company name
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.setTextColor(30, 64, 175);
        doc.text('Clairvoyance', margin, 30);
      }

      // Dashboard title
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(36);
      doc.setTextColor(30, 58, 138);
      doc.text(reportData.name, pageWidth / 2, 70, { align: 'center' });
      
      // Dashboard description
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(12);
      doc.setTextColor(75, 85, 99);
      if (reportData.description) {
        const descLines = doc.splitTextToSize(reportData.description, contentWidth - 40);
        doc.text(descLines, pageWidth / 2, 80, { align: 'center' });
      }

      // Add a summary box
      doc.setFillColor(249, 250, 251);
      doc.rect(margin, 110, contentWidth, 40, 'F');
      doc.setDrawColor(229, 231, 235);
      doc.rect(margin, 110, contentWidth, 40, 'S');
      
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(12);
      doc.setTextColor(30, 58, 138);
      doc.text('Report Summary', margin + 5, 120);
      
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(10);
      doc.setTextColor(75, 85, 99);
      doc.text(`Total Widgets: ${reportData.widgets.length}`, margin + 5, 130);
      doc.text(`Generated: ${new Date().toLocaleDateString()}`, margin + 5, 135);
      doc.text(`Dashboard ID: ${dashboardId}`, margin + 5, 140);

      // Add a decorative line
      doc.setDrawColor(30, 58, 138);
      doc.setLineWidth(2);
      doc.line(margin, 160, pageWidth - margin, 160);

      // --- Enhanced Widgets Pages ---
      doc.addPage();
      let currentPage = 2;
      addHeader(reportData.name, currentPage);
      addFooter(currentPage);
      
      let yPos = 25;
      const widgetSpacing = 8;
      const maxWidgetHeight = 75; // Reduced to fit more widgets per page

      // Group widgets by type for better layout
      const chartWidgets = reportData.widgets.filter((w:any) => ['line', 'bar', 'pie', 'doughnut'].includes(w.type));
      const valueWidgets = reportData.widgets.filter((w:any) => w.type === 'single-value');

      // Render value widgets first (they're smaller and can fit more per page)
      for (const widget of valueWidgets) {
        const element = document.getElementById(`widget-container-${widget.id}`);
        if (!element) {
          console.warn(`Widget container not found for widget ${widget.id}`);
          continue;
        }

        // Check if we need a new page
        if (yPos + maxWidgetHeight > pageHeight - 30) {
          doc.addPage();
          currentPage++;
          addHeader(reportData.name, currentPage);
          addFooter(currentPage);
          yPos = 25;
        }

        // Render widget with enhanced styling
        await renderWidgetToPDF(doc, element, widget, margin, yPos, contentWidth, maxWidgetHeight);
        yPos += maxWidgetHeight + widgetSpacing;
      }

      // Render chart widgets (they need more space)
      for (const widget of chartWidgets) {
        const element = document.getElementById(`widget-container-${widget.id}`);
        if (!element) {
          console.warn(`Widget container not found for widget ${widget.id}`);
          continue;
        }

        // Check if we need a new page
        if (yPos + maxWidgetHeight > pageHeight - 30) {
          doc.addPage();
          currentPage++;
          addHeader(reportData.name, currentPage);
          addFooter(currentPage);
          yPos = 25;
        }

        // Render widget with enhanced styling
        await renderWidgetToPDF(doc, element, widget, margin, yPos, contentWidth, maxWidgetHeight);
        yPos += maxWidgetHeight + widgetSpacing;
      }

      doc.save(`${reportData.name.replace(/\s/g, '_')}_Report_${new Date().toISOString().split('T')[0]}.pdf`);
    } catch (e) {
      console.error('Failed to generate report:', e);
      alert(`Failed to generate report: ${e instanceof Error ? e.message : 'Unknown error'}`);
    } finally {
      isGeneratingReport = false;
    }
  }

  // Enhanced helper function to render individual widgets to PDF
  async function renderWidgetToPDF(doc: any, element: HTMLElement, widget: any, x: number, y: number, width: number, maxHeight: number) {
    // Add widget title with consistent Helvetica font
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.setTextColor(30, 58, 138);
    doc.text(widget.title || `Widget ${widget.id}`, x, y);
    
    // Add widget type badge
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(75, 85, 99);
    doc.text(`Type: ${widget.type}`, x + width - 30, y);
    
    // Add a subtle border around the widget area
    doc.setDrawColor(229, 231, 235);
    doc.setLineWidth(0.5);
    doc.rect(x, y + 2, width, maxHeight - 5, 'S');

    // Capture widget screenshot
    const originalBg = element.style.backgroundColor;
    element.style.backgroundColor = 'white';
    const canvas = await html2canvas(element, { 
      scale: 2, 
      logging: false, 
      useCORS: true,
      backgroundColor: '#ffffff'
    });
    element.style.backgroundColor = originalBg;

    const imgData = canvas.toDataURL('image/png');
    const imgProps = doc.getImageProperties(imgData);
    
    // Calculate optimal image dimensions - better space utilization
    const aspectRatio = imgProps.width / imgProps.height;
    let imgWidth = width - 10; // Leave some padding
    let imgHeight = imgWidth / aspectRatio;
    
    // Ensure image fits within max height but try to use more space
    if (imgHeight > maxHeight - 20) {
      imgHeight = maxHeight - 20;
      imgWidth = imgHeight * aspectRatio;
    }
    
    // Center the image horizontally
    const imgX = x + (width - imgWidth) / 2;
    const imgY = y + 8;
    
    doc.addImage(imgData, 'PNG', imgX, imgY, imgWidth, imgHeight);
    
    // Add widget metadata at the bottom with consistent font
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(107, 114, 128);
    const metadataY = y + maxHeight - 8;
    if (widget.kpi_id) {
      doc.text(`KPI ID: ${widget.kpi_id}`, x + 5, metadataY);
    }
    if (widget.aggregation) {
      doc.text(`Aggregation: ${widget.aggregation}`, x + width - 50, metadataY);
    }
  }

  function handleViewEntries(event: CustomEvent) {
    selectedKpiForEntries = event.detail.kpi;
    selectedKpiIdForEntries = event.detail.kpiId;
    isKpiEntriesModalOpen = true;
  }

  function handleEntriesUpdated(event: CustomEvent) {
    // When KPI entries are updated, refresh the dashboard to update all widgets
    fetchDashboard();
  }

  $: dashboardId = $page.params.id;
  $: isEditor = $user?.role === 'editor';

  async function fetchDashboard() {
    loading.set(true);
    error.set(null);
    try {
      const response: ApiResponse<{ dashboard: Dashboard }> = await api.getDashboard(parseInt(dashboardId));
      
      if (response.success && response.data?.dashboard) {
        dashboard.set(response.data.dashboard);
      } else {
        error.set(response.message || 'Invalid dashboard data received.');
        dashboard.set(null);
      }
    } catch (e) {
      error.set(e instanceof Error ? e.message : 'Failed to load dashboard');
      dashboard.set(null);
    }
    loading.set(false);
  }

  onMount(() => {
    fetchDashboard();
  });

  $: widgetsArr = (() => {
    const layoutData = $dashboard?.layout;
    if (typeof layoutData === 'string') {
      try {
        const parsed = JSON.parse(layoutData);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        console.error("Failed to parse widgets JSON:", e);
        return [];
      }
    }
    if (Array.isArray(layoutData)) {
      return layoutData;
    }
    return [];
  })();

  let items: any[] = [];
  $: if (widgetsArr) {
    items = widgetsArr.map((widget, i) => {
      const layout = {
        x: widget.x ?? (i % 4) * 3,
        y: widget.y ?? Math.floor(i / 4) * 5,
        w: widget.w ?? 3,
        h: widget.h ?? 5,
        fixed: !editMode,
        min: { w: 2, h: 4 },
        customDragger: true
      };

      return {
        [cols[0][1]]: gridHelp.item(layout),
        ...widget,
        id: String(widget.id ?? widget.position ?? i)
      };
    });
  }

  const { openWidgetSettings, savedWidget }: { openWidgetSettings: (widget: any) => void; savedWidget: Writable<any | null> } = getContext('dashboard-layout');

  $: if ($savedWidget) {
    items = items.map(item => (item.id === $savedWidget.id ? { ...item, ...$savedWidget } : item));
    $savedWidget = null;
  }
</script>

<style>
  :global(.svlt-grid-container) {
    background: #eee;
  }
  :global(.svlt-grid-shadow) {
    background: rgba(0, 0, 0, 0.2);
  }
  :global(.svlt-grid-resizer) {
    background: #fff;
    border: 2px solid #333;
  }
</style>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
  {#if $loading}
    <div class="text-center py-12">Loading...</div>
  {:else if $error}
    <div id="widget-container-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <strong class="font-bold">Error:</strong>
      <span class="block sm:inline">{$error}</span>
    </div>
  {:else if !$user}
    <div class="text-center py-12 text-gray-600">Please log in to view this dashboard.</div>
  {:else if !$dashboard}
    <div class="text-center py-12 text-gray-600">Dashboard not found or you do not have access.</div>
  {:else}
    <!-- Page Header -->
    <div class="mb-6">
      <div class="mb-2 text-sm text-gray-500">
        <a href="/editor/dashboards" class="hover:underline">Dashboards</a>
        <span class="mx-2">/</span>
        <span class="font-medium text-gray-700">{$dashboard.name}</span>
      </div>
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{$dashboard.name}</h1>
          {#if $dashboard.description}
            <p class="mt-2 text-sm text-gray-600">{$dashboard.description}</p>
          {/if}
        </div>

        {#if isEditor}
          <div class="flex items-center space-x-2">
            {#if editMode}
            <button on:click={handleCancel} class="bg-white hover:bg-gray-100 text-blue-900 font-medium py-2 px-4 text-sm rounded-full border border-blue-900 inline-flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              Cancel
            </button>
              <button on:click={addWidget} class="bg-blue-900 hover:bg-blue-800 text-white font-medium py-2 px-4 text-sm rounded-full inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Add Widget
              </button>
              <button on:click={handleSave} class="bg-blue-900 hover:bg-blue-800 text-white font-medium py-2 px-4 text-sm rounded-full inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                Save Layout
              </button>
            {:else}
              <button on:click={() => isViewersModalOpen = true} class="bg-white hover:bg-gray-100 text-blue-900 font-medium py-2 px-4 text-sm rounded-full border border-blue-900 inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.522 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542 7z" /></svg>
                Manage Viewers
              </button>
              <button on:click={generateReport} class="bg-white hover:bg-gray-100 text-blue-900 font-medium py-2 px-4 text-sm rounded-full border border-blue-900 inline-flex items-center justify-center" disabled={isGeneratingReport}>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Generate Report
              </button>
              <button on:click={() => editMode = true} class="bg-blue-900 hover:bg-blue-800 text-white font-medium py-2 px-4 text-sm rounded-full inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z" /></svg>
                Edit Layout
              </button>
            {/if}
          </div>
        {/if}
      </div>
    </div>

    <div class="mt-8">
      {#if items.length > 0}
        <div class="svelte-grid-container -mx-2">
          <Grid {cols} bind:items={items} rowHeight={40} let:item let:dataItem let:movePointerDown>
            <div id="widget-container-{dataItem.id}" class="h-full w-full p-2">
              <DashboardWidget
                  widget={dataItem}
                  {editMode}
                  {movePointerDown}
                  on:openSettings={(event) => openWidgetSettings(event.detail.widget)}
                  on:remove={(event) => removeWidget(event.detail.id)}
                  on:viewEntries={handleViewEntries}
                />
            </div>
          </Grid>
        </div>
      {:else}
        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-lg">
          <h3 class="text-lg font-medium">No widgets yet</h3>
          <p class="mt-1 text-sm">This dashboard doesn't have any widgets configured.</p>
          {#if isEditor}
            <div class="mt-6">
              <button 
                on:click={() => {
                  editMode = true;
                  // Use a small delay to ensure edit mode is set before opening the modal
                  setTimeout(() => {
                    addWidget();
                  }, 100);
                }} 
                class="bg-blue-900 hover:bg-blue-800 text-white font-medium py-2 px-4 text-sm rounded-full inline-flex items-center justify-center"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Your First Widget
              </button>
            </div>
          {/if}
        </div>
      {/if}
    </div>
  {/if}
</div>

{#if $dashboard}
  <ViewersModal 
    bind:isOpen={isViewersModalOpen} 
    dashboardId={dashboardId}
    viewers={[]}
    on:update={fetchDashboard}
  />
{/if}

<!-- Widget Settings Modal for new widgets -->
<WidgetSettingsModal
  bind:show={isWidgetSettingsModalOpen}
  widget={newWidgetTemplate}
  on:save={handleWidgetSettingsSave}
  on:close={() => {
    isWidgetSettingsModalOpen = false;
    newWidgetTemplate = null;
  }}
/>

<!-- KPI Entries Modal -->
<KpiEntriesModal
  bind:isOpen={isKpiEntriesModalOpen}
  kpi={selectedKpiForEntries}
  kpiId={selectedKpiIdForEntries}
  on:close={() => {
    isKpiEntriesModalOpen = false;
    selectedKpiForEntries = null;
    selectedKpiIdForEntries = null;
  }}
  on:entriesUpdated={handleEntriesUpdated}
/> 