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
      const { 
        id, title, type, kpi_id, backgroundColor, borderColor, startDate, endDate, 
        aggregation, showLegend, legendPosition, gapThresholdDays, timeUnit, 
        aggregationMethod, chartMode, categoryField, rangeMethod, customRanges  // Removed categoryType, timePeriod
      } = dataItem;
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
        legendPosition,
        gapThresholdDays,
        timeUnit,
        aggregationMethod,
        chartMode,
        categoryField,
        rangeMethod,       // Keep this
        customRanges       // Keep this
        // Removed categoryType, timePeriod
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
      gapThresholdDays: 7,
      timeUnit: 'day',           // Add this
      aggregationMethod: 'average', // Add this
      chartMode: 'time-based',   // Add this
      categoryField: 'date',     // Add this
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
      legendPosition: widgetData.legendPosition,
      gapThresholdDays: widgetData.gapThresholdDays,
      timeUnit: widgetData.timeUnit,
      aggregationMethod: widgetData.aggregationMethod,
      chartMode: widgetData.chartMode,        // Add this
      categoryField: widgetData.categoryField, // Add this
      categoryType: widgetData.categoryType,  // Add this
      rangeMethod: widgetData.rangeMethod,    // Add this
      timePeriod: widgetData.timePeriod,     // Add this
      customRanges: widgetData.customRanges   // Add this
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

  // Enhanced helper function to render individual widgets to PDF with side-by-side layout
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

    // Calculate layout: Chart on left (60%), Insights on right (40%)
    const chartWidth = width * 0.6;
    const insightsWidth = width * 0.4;
    const chartX = x + 2;
    const insightsX = x + chartWidth + 2;
    const chartY = y + 8;
    const insightsY = y + 8;
    const availableHeight = maxHeight - 15;

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
    
    // Calculate chart dimensions to fit in left side
    const aspectRatio = imgProps.width / imgProps.height;
    let chartImgWidth = chartWidth - 4;
    let chartImgHeight = chartImgWidth / aspectRatio;
    
    // Ensure chart fits within available height
    if (chartImgHeight > availableHeight) {
      chartImgHeight = availableHeight;
      chartImgWidth = chartImgHeight * aspectRatio;
    }
    
    // Center the chart vertically
    const chartImgX = chartX + (chartWidth - chartImgWidth) / 2;
    const chartImgY = chartY + (availableHeight - chartImgHeight) / 2;
    
    // Add chart image
    doc.addImage(imgData, 'PNG', chartImgX, chartImgY, chartImgWidth, chartImgHeight);
    
    // Add insights on the right side if KPI data is available
    if (widget.kpi_id) {
      try {
        const insights = await generateKpiInsights(widget.kpi_id, widget.type, widget);
        if (insights) {
          await addInsightsToPDF(doc, insights, insightsX, insightsY, insightsWidth - 4, availableHeight);
        }
      } catch (e) {
        console.warn('Could not generate insights for widget:', widget.id, e);
      }
    }
    
    // Add widget metadata at the bottom
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(107, 114, 128);
    const metadataY = y + maxHeight - 8;
  }

  // Enhanced function to generate KPI insights that matches chart logic
  async function generateKpiInsights(kpiId: number, widgetType: string, widget: any) {
    try {
      // Fetch KPI details
      const kpiResponse = await api.getKpi(kpiId);
      if (!kpiResponse.success) return null;
      
      const kpi = kpiResponse.data?.kpi;
      if (!kpi) return null;

      // Fetch KPI entries with widget's date range
      const entriesResponse = await api.getKpiEntries(kpiId, widget.startDate, widget.endDate);
      if (!entriesResponse.success) return null;
      
      let entries = entriesResponse.data?.entries || [];
      if (entries.length < 2) return null;

      // Sort entries by date
      const sortedEntries = entries.sort((a: any, b: any) => new Date(a.date).getTime() - new Date(b.date).getTime());
      
      // For line charts, apply the same aggregation logic as the chart
      if (widgetType === 'line' && widget.timeUnit && widget.aggregationMethod) {
        entries = aggregateDataByTimeUnit(sortedEntries, widget.timeUnit, widget.aggregationMethod);
      }
      
      if (entries.length < 2) return null;
      
      // Calculate trends using aggregated data
      const latestValue = Number(entries[entries.length - 1].value);
      const previousValue = Number(entries[entries.length - 2].value);
      const firstValue = Number(entries[0].value);
      
      const recentChange = latestValue - previousValue;
      const totalChange = latestValue - firstValue;
      const recentChangePercent = previousValue !== 0 ? (recentChange / previousValue) * 100 : 0;
      const totalChangePercent = firstValue !== 0 ? (totalChange / firstValue) * 100 : 0;
      
      // Calculate trend direction
      const recentTrend = recentChange > 0 ? 'increasing' : recentChange < 0 ? 'decreasing' : 'stable';
      const totalTrend = totalChange > 0 ? 'increasing' : totalChange < 0 ? 'decreasing' : 'stable';
      
      // Calculate average from aggregated data
      const average = entries.reduce((sum: number, entry: any) => sum + Number(entry.value), 0) / entries.length;
      
      // Calculate volatility (standard deviation) from aggregated data
      const variance = entries.reduce((sum: number, entry: any) => {
        const diff = Number(entry.value) - average;
        return sum + (diff * diff);
      }, 0) / entries.length;
      const volatility = Math.sqrt(variance);
      
      // Performance vs target
      const target = Number(kpi.target);
      const performanceVsTarget = target !== 0 ? (latestValue / target) * 100 : 0;
      
      // Add aggregation info for context
      const aggregationInfo = widgetType === 'line' && widget.timeUnit && widget.aggregationMethod 
        ? `${widget.timeUnit.charAt(0).toUpperCase() + widget.timeUnit.slice(1)} ${widget.aggregationMethod.charAt(0).toUpperCase() + widget.aggregationMethod.slice(1)}`
        : 'Raw Data';
      
      return {
        kpi,
        latestValue,
        previousValue,
        recentChange,
        recentChangePercent,
        totalChange,
        totalChangePercent,
        recentTrend,
        totalTrend,
        average,
        volatility,
        performanceVsTarget,
        entryCount: entries.length,
        aggregationInfo,
        dateRange: {
          start: entries[0].date,
          end: entries[entries.length - 1].date
        }
      };
    } catch (e) {
      console.error('Error generating insights:', e);
      return null;
    }
  }

  // Helper function to aggregate data by time unit (same logic as DashboardWidget)
  function aggregateDataByTimeUnit(entries: any[], timeUnit: string, aggregationMethod: string): any[] {
    if (timeUnit === 'day') {
      return entries; // No aggregation needed for daily data
    }

    const groupedData = new Map<string, any[]>();

    // Group entries by time unit
    entries.forEach(entry => {
      const date = new Date(entry.date);
      let key: string;

      switch (timeUnit) {
        case 'week':
          // Get start of week (Monday)
          const startOfWeek = new Date(date);
          startOfWeek.setDate(date.getDate() - date.getDay() + 1);
          key = startOfWeek.toISOString().split('T')[0];
          break;
        case 'month':
          key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;
          break;
        case 'year':
          key = `${date.getFullYear()}-01-01`;
          break;
        default:
          key = entry.date;
      }

      if (!groupedData.has(key)) {
        groupedData.set(key, []);
      }
      groupedData.get(key)!.push(entry);
    });

    // Aggregate each group
    const aggregatedEntries: any[] = [];
    groupedData.forEach((group, key) => {
      const values = group.map(entry => Number(entry.value));
      let aggregatedValue: number;

      switch (aggregationMethod) {
        case 'sum':
          aggregatedValue = values.reduce((sum, val) => sum + val, 0);
          break;
        case 'max':
          aggregatedValue = Math.max(...values);
          break;
        case 'min':
          aggregatedValue = Math.min(...values);
          break;
        case 'latest':
          // Sort by date and take the latest
          const sortedGroup = group.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
          aggregatedValue = Number(sortedGroup[0].value);
          break;
        case 'average':
        default:
          aggregatedValue = values.reduce((sum, val) => sum + val, 0) / values.length;
          break;
      }

      aggregatedEntries.push({
        id: group[0].id, // Use first entry's ID
        kpi_id: group[0].kpi_id,
        date: key,
        value: aggregatedValue
      });
    });

    // Sort aggregated entries by date
    return aggregatedEntries.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
  }

  // Enhanced function to add insights to PDF with vertical centering and dynamic colors
  async function addInsightsToPDF(doc: any, insights: any, x: number, y: number, width: number, availableHeight: number) {
    if (!insights) return;

    const { kpi, latestValue, recentChange, recentChangePercent, recentTrend, average, performanceVsTarget, entryCount } = insights;
    
    // Create insights box with better styling
    doc.setFillColor(248, 250, 252);
    doc.rect(x, y, width, availableHeight, 'F');
    doc.setDrawColor(229, 231, 235);
    doc.rect(x, y, width, availableHeight, 'S');
    
    // Insights title
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(30, 58, 138);
    doc.text('Key Insights', x + 3, y + 6);
    
    // Add a subtle line under the title
    doc.setDrawColor(30, 58, 138);
    doc.setLineWidth(0.5);
    doc.line(x + 3, y + 8, x + width - 3, y + 8);
    
    // Calculate vertical centering for the content area
    const contentStartY = y + 12;
    const contentHeight = availableHeight - 12;
    const totalLines = 5; // Current Value, Recent Change, Target Performance, Average, Data Points
    const lineHeight = 6;
    const sectionSpacing = 2;
    const totalContentHeight = (totalLines * lineHeight) + ((totalLines - 1) * sectionSpacing);
    const startY = contentStartY + (contentHeight - totalContentHeight) / 2;
    
    let currentY = startY;
    const labelWidth = 40; // Space reserved for labels
    
    // Current value section - single text line
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(30, 58, 138);
    const currentValueText = `Latest Value: ${kpi.format_prefix || ''}${latestValue.toLocaleString()}${kpi.format_suffix || ''}`;
    doc.text(currentValueText, x + 3, currentY);
    currentY += lineHeight + sectionSpacing;
    
    // Recent change section - with dynamic color and proper positioning
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(30, 58, 138);
    doc.text('Recent Change:', x + 3, currentY);
    
    // Add the value with dynamic color at the right position
    doc.setFont('helvetica', 'normal');
    const changeColor = recentChange > 0 ? [34, 197, 94] : recentChange < 0 ? [239, 68, 68] : [107, 114, 128];
    doc.setTextColor(changeColor[0], changeColor[1], changeColor[2]);
    const changeDirection = recentChange > 0 ? 'up' : recentChange < 0 ? 'down' : 'stable';
    const changeValueText = `${Math.abs(recentChangePercent).toFixed(1)}% ${changeDirection} (${recentTrend})`;
    doc.text(changeValueText, x + labelWidth - 7, currentY);
    currentY += lineHeight + sectionSpacing;
    
    // Performance vs target section - with dynamic color and proper positioning
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(30, 58, 138);
    doc.text('Target Performance:', x + 3, currentY);
    
    // Add the value with dynamic color at the right position
    doc.setFont('helvetica', 'normal');
    const targetColor = performanceVsTarget >= 100 ? [34, 197, 94] : performanceVsTarget >= 80 ? [251, 191, 36] : [239, 68, 68];
    doc.setTextColor(targetColor[0], targetColor[1], targetColor[2]);
    const targetStatus = performanceVsTarget >= 100 ? 'Above Target' : performanceVsTarget >= 80 ? 'Near Target' : 'Below Target';
    const targetValueText = `${performanceVsTarget.toFixed(1)}% (${targetStatus})`;
    doc.text(targetValueText, x + labelWidth - 7, currentY);
    currentY += lineHeight + sectionSpacing;
    
    // Average section - single text line
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(30, 58, 138);
    const averageText = `Average: ${kpi.format_prefix || ''}${average.toFixed(1)}${kpi.format_suffix || ''}`;
    doc.text(averageText, x + 3, currentY);
    currentY += lineHeight + sectionSpacing;
    
    // Data points section - single text line
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(30, 58, 138);
    const dataPointsText = `Data Points: ${entryCount} entries`;
    doc.text(dataPointsText, x + 3, currentY);
    
    // Add a subtle border around the insights
    doc.setDrawColor(30, 58, 138);
    doc.setLineWidth(0.3);
    doc.rect(x + 1, y + 1, width - 2, availableHeight - 2, 'S');
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