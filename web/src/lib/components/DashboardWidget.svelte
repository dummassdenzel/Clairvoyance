<script lang="ts">
  import { onMount, onDestroy, createEventDispatcher } from 'svelte';
  // Dynamic imports for Chart.js and plugins (browser-only)
  // import Chart from 'chart.js/auto';
  // import annotationPlugin from 'chartjs-plugin-annotation';
  // import zoomPlugin from 'chartjs-plugin-zoom';
  // import ChartDataLabels from 'chartjs-plugin-datalabels';
  // import 'chartjs-adapter-date-fns';
  import * as api from '$lib/services/api';
  import type { Kpi, ApiResponse, KpiEntry } from '$lib/types';


  // Chart.js will be loaded dynamically
  let Chart: any = null;
  let chartLoaded = false;

  export let widget: any;
  export let editMode: boolean = false;
  export let movePointerDown: (e: PointerEvent) => void = () => {};
  export let canEdit: boolean = true;
  export let canViewEntries: boolean = true;

  const dispatch = createEventDispatcher();

  // Load Chart.js and plugins dynamically
  async function loadChartJS() {
    if (chartLoaded || typeof window === 'undefined') return;
    
    try {
      const [
        chartModule,
        annotationPluginModule,
        zoomPluginModule,
        dataLabelsModule
      ] = await Promise.all([
        import('chart.js/auto'),
        import('chartjs-plugin-annotation'),
        import('chartjs-plugin-zoom'),
        import('chartjs-plugin-datalabels')
      ]);

      // Import the date adapter
      try {
        await import('chartjs-adapter-date-fns');
      } catch (e) {
        console.warn('chartjs-adapter-date-fns not available:', e);
      }

      Chart = chartModule.default;
      const annotationPlugin = annotationPluginModule.default;
      const zoomPlugin = zoomPluginModule.default;
      const ChartDataLabels = dataLabelsModule.default;

      // Register plugins
      Chart.register(annotationPlugin, zoomPlugin, ChartDataLabels);
      chartLoaded = true;
    } catch (e) {
      console.error('Failed to load Chart.js:', e);
    }
  }

  let isConfirmingDelete = false;

  let chartInstance: any = null;
  let kpiData: { labels: string[]; values: number[] } | null = null;
  let kpiDetails: Kpi | null = null;
  let aggregateValue: number | null = null;
  let dateRange: { start?: string; end?: string } | null = null; // Add date range tracking
  let isLoading = true;
  let error: string | null = null;
  let canvasElement: HTMLCanvasElement;

  let isDragging = false;
  let dragStartX = 0;
  let dragStartY = 0;
  let dragThreshold = 5; // pixels

  async function loadWidgetData() {
    if (!widget.kpi_id) {
      error = 'No KPI selected for this widget.';
      isLoading = false;
      return;
    }

    isLoading = true;
    error = null;

    try {
      const detailsPromise = api.getKpi(widget.kpi_id);
      let dataPromise;

      if (widget.type === 'single-value') {
        dataPromise = api.getAggregateKpiValue(widget.kpi_id, widget.aggregation || 'latest', widget.startDate, widget.endDate);
      } else {
        dataPromise = api.getKpiEntries(widget.kpi_id, widget.startDate, widget.endDate);
      }

      const [detailsResult, dataResult] = await Promise.all([detailsPromise, dataPromise]);

      if (detailsResult.success) {
        kpiDetails = detailsResult.data?.kpi || null;
      } else {
        throw new Error(detailsResult.message || 'Failed to load KPI details');
      }

      if (dataResult.success && dataResult.data) {
        if (widget.type === 'single-value') {
          // For single-value widgets, dataResult.data should have a 'value' property
          const valueData = dataResult.data as { value: number };
          aggregateValue = valueData.value !== null ? Number(valueData.value) : null;
          kpiData = null;
          
          // Get date range for single-value widgets
          await loadDateRange();
        } else {
          // For chart widgets, handle different possible data structures
          let entries: KpiEntry[] = [];
          
          // Type guard to check if data is an array
          if (Array.isArray(dataResult.data)) {
            entries = dataResult.data as KpiEntry[];
          } else {
            // Type guard to check if data has entries property
            const dataWithEntries = dataResult.data as { entries?: KpiEntry[] };
            if (dataWithEntries.entries && Array.isArray(dataWithEntries.entries)) {
              entries = dataWithEntries.entries;
            } else {
              console.error('Unexpected data structure:', dataResult.data);
              throw new Error('Invalid data structure received');
            }
          }

          // Check if we have any entries
          if (entries.length === 0) {
            error = 'No data available for this KPI.';
            kpiData = null;
          } else {
            // Aggregate entries by date to handle multiple entries per date
            const aggregatedEntries = aggregateDataByTimeUnit(
              entries, 
              'day', // Use daily aggregation to group by exact date
              widget.aggregationMethod || 'sum' // Default to sum for multiple entries
            );
            
            kpiData = {
              labels: aggregatedEntries.map((d: KpiEntry) => d.date),
              values: aggregatedEntries.map((d: KpiEntry) => Number(d.value))
            };
          }
          aggregateValue = null;
          dateRange = null; // Reset date range for chart widgets
        }
      } else {
        // If aggregate fetch fails for single-value, show empty state instead of error banner
        if (widget.type === 'single-value') {
          aggregateValue = null;
          kpiData = null;
          dateRange = null;
          // keep error null so single-value branch renders its empty message
        } else {
          throw new Error(dataResult.message || 'Failed to load widget data');
        }
      }
    } catch (e) {
      console.error(`Failed to load data for widget ${widget.id}:`, e);
      if (widget.type === 'single-value') {
        // Suppress global error for single-value so the widget shows unified empty state
        error = null;
        kpiData = null;
        // keep kpiDetails as-is if we have it; otherwise it's fine to be null
        aggregateValue = null;
        dateRange = null;
      } else {
        error = e instanceof Error ? e.message : 'Unknown error occurred';
        kpiData = null;
        kpiDetails = null;
        aggregateValue = null;
        dateRange = null;
      }
    } finally {
      isLoading = false;
    }
  }

  // New function to load date range for single-value widgets
  async function loadDateRange() {
    if (!widget.kpi_id || widget.type !== 'single-value') return;

    try {
      // Fetch all entries to determine date range
      const entriesResponse = await api.getKpiEntries(widget.kpi_id, widget.startDate, widget.endDate);
      
      if (entriesResponse.success && entriesResponse.data) {
        let entries: KpiEntry[] = [];
        
        if (Array.isArray(entriesResponse.data)) {
          entries = entriesResponse.data as KpiEntry[];
        } else {
          const dataWithEntries = entriesResponse.data as { entries?: KpiEntry[] };
          if (dataWithEntries.entries && Array.isArray(dataWithEntries.entries)) {
            entries = dataWithEntries.entries;
          }
        }

        if (entries.length > 0) {
          // Sort entries by date to get start and end dates
          const sortedEntries = entries.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
          dateRange = {
            start: sortedEntries[0].date,
            end: sortedEntries[sortedEntries.length - 1].date
          };
        } else {
          dateRange = null;
        }
      }
    } catch (e) {
      console.warn(`Failed to load date range for widget ${widget.id}:`, e);
      dateRange = null;
    }
  }

  // Helper function to format date range text
  function getDateRangeText(): string {
    if (!dateRange) return '';
    
    const { start, end } = dateRange;
    if (!start) return '';
    
    const formatDate = (dateStr: string) => {
      const date = new Date(dateStr);
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      });
    };
    
    if (start === end) {
      return `on ${formatDate(start)}`;
    } else {
      return `since ${formatDate(start)}${end ? ` to ${formatDate(end)}` : ''}`;
    }
  }

  async function loadKpiDetails() {
    if (!widget.kpi_id || kpiDetails) return kpiDetails;

    try {
      const response: ApiResponse<{ kpi: Kpi }> = await api.getKpi(widget.kpi_id);
      if (response.success && response.data?.kpi) {
        kpiDetails = response.data.kpi;
        return kpiDetails;
      } else {
        console.error(`Widget ${widget.id}: Failed to load KPI details`, response.message);
        return null;
      }
    } catch (e) {
      console.error(`Widget ${widget.id}: Error loading KPI details`, e);
      return null;
    }
  }

  function getRagClass(value: number | null, details: Kpi | null): string {
    if (value === null || !details) return 'text-gray-900';

    const { direction, rag_red, rag_amber } = details;
    const redThreshold = Number(rag_red);
    const amberThreshold = Number(rag_amber);

    if (isNaN(redThreshold) || isNaN(amberThreshold)) return 'text-gray-900';

    if (direction === 'higher_is_better') {
      if (value < redThreshold) return 'text-red-500';
      if (value < amberThreshold) return 'text-yellow-500';
      return 'text-blue-900';
    } else if (direction === 'lower_is_better') {
      if (value > redThreshold) return 'text-red-500';
      if (value > amberThreshold) return 'text-yellow-500';
      return 'text-blue-900';
    }

    return 'text-gray-900';
  }

  async function handleWidgetClick(event: MouseEvent) {
    // Check if this was a drag/pan gesture
    if (isDragging) {
      return;
    }

    // Check if the click was on the legend area specifically
    const target = event.target as HTMLElement;
    
    // If clicking on canvas, we need to check if it's in the legend area
    if (target.tagName === 'CANVAS') {
      const canvas = target as HTMLCanvasElement;
      const rect = canvas.getBoundingClientRect();
      const x = event.clientX - rect.left;
      const y = event.clientY - rect.top;
      
      // Get the chart instance to check if click is in legend area
      if (chartInstance) {
        const legend = chartInstance.legend;
        if (legend && legend.options && legend.options.display) {
          // Check if click is within legend bounds
          const legendBox = legend.legendItems;
          if (legendBox && legendBox.length > 0) {
            // Get legend position and dimensions
            const legendLeft = legend.left;
            const legendTop = legend.top;
            const legendWidth = legend.width;
            const legendHeight = legend.height;
            
            // Check if click is within legend area
            if (x >= legendLeft && x <= legendLeft + legendWidth && 
                y >= legendTop && y <= legendTop + legendHeight) {
              // Click is on legend - don't open modal
              return;
            }
          }
        }
      }
    }

    // Only handle clicks in view mode (not edit mode), when we have a KPI ID, and user can view entries
    if (!editMode && widget.kpi_id && canViewEntries) {
      // Load KPI details if not already loaded
      let details = kpiDetails;
      if (!details) {
        details = await loadKpiDetails();
      }

      if (details) {
        dispatch('viewEntries', { 
          kpi: details, 
          kpiId: widget.kpi_id 
        });
      } else {
        console.error(`Widget ${widget.id}: Failed to load KPI details for modal`);
      }
    } 
  }

  function handleMouseDown(event: MouseEvent) {
    isDragging = false;
    dragStartX = event.clientX;
    dragStartY = event.clientY;
  }

  function handleMouseMove(event: MouseEvent) {
    if (dragStartX === 0 && dragStartY === 0) return;
    
    const deltaX = Math.abs(event.clientX - dragStartX);
    const deltaY = Math.abs(event.clientY - dragStartY);
    
    if (deltaX > dragThreshold || deltaY > dragThreshold) {
      isDragging = true;
    }
  }

  function handleMouseUp() {
    // Reset dragging state after a short delay to allow click handler to check it
    setTimeout(() => {
      isDragging = false;
      dragStartX = 0;
      dragStartY = 0;
    }, 10);
  }

  // When data-related properties change, re-fetch all data
  $: if (widget.kpi_id || widget.startDate || widget.endDate || widget.type || widget.aggregation) {
    loadWidgetData();
  }

  // Enhanced function to process time-series data with gap detection and aggregation
  function processTimeSeriesData(entries: KpiEntry[], gapThresholdDays: number = 7, timeUnit: string = 'day', aggregationMethod: string = 'average') {
    if (!entries || entries.length === 0) return { data: [], gaps: [], segments: [] };

    // Sort entries by date
    const sortedEntries = entries.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    
    // Aggregate data by time unit
    const aggregatedData = aggregateDataByTimeUnit(sortedEntries, timeUnit, aggregationMethod);
    
    const data: any[] = [];
    const gaps: { start: string; end: string; days: number }[] = [];
    const segments: any[][] = []; // Array of data segments
    let currentSegment: any[] = [];
    
    for (let i = 0; i < aggregatedData.length; i++) {
      const currentEntry = aggregatedData[i];
      const nextEntry = aggregatedData[i + 1];
      
      // Add current data point to current segment
      currentSegment.push({
        x: new Date(currentEntry.date),
        y: Number(currentEntry.value)
      });
      
      // Check for gap to next entry
      if (nextEntry) {
        const currentDate = new Date(currentEntry.date);
        const nextDate = new Date(nextEntry.date);
        const daysDiff = Math.ceil((nextDate.getTime() - currentDate.getTime()) / (1000 * 60 * 60 * 24));
        
        if (daysDiff > gapThresholdDays) {
          // Gap detected - end current segment and start new one
          gaps.push({
            start: currentEntry.date,
            end: nextEntry.date,
            days: daysDiff
          });
          
          // Save current segment and start new one
          if (currentSegment.length > 0) {
            segments.push([...currentSegment]);
            currentSegment = [];
          }
        }
      }
    }
    
    // Add the final segment if it has data
    if (currentSegment.length > 0) {
      segments.push(currentSegment);
    }
    
    return { data, gaps, segments };
  }

  // New function to aggregate data by time unit
  function aggregateDataByTimeUnit(entries: KpiEntry[], timeUnit: string, aggregationMethod: string): KpiEntry[] {
    // Always group and aggregate, even for daily data, to handle multiple entries per date

    const groupedData = new Map<string, KpiEntry[]>();

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
    const aggregatedEntries: KpiEntry[] = [];
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

  // New function to process categorical data for bar charts
  function processCategoricalData(entries: KpiEntry[], categoryField: string = 'date') {
    if (!entries || entries.length === 0) return { data: [], labels: [] };

    const groupedData = new Map<string, KpiEntry[]>();

    entries.forEach(entry => {
      let key: string;

      if (categoryField === 'date') {
        // Group by date (daily)
        key = entry.date;
      } else if (categoryField === 'value') {
        // Group by value ranges
        const value = Number(entry.value);
        if (value < 100) key = '0-99';
        else if (value < 500) key = '100-499';
        else if (value < 1000) key = '500-999';
        else key = '1000+';
      } else {
        key = entry.date; // fallback
      }

      if (!groupedData.has(key)) {
        groupedData.set(key, []);
      }
      groupedData.get(key)!.push(entry);
    });

    // Convert to chart data format
    const labels: string[] = [];
    const data: number[] = [];

    groupedData.forEach((group, key) => {
      labels.push(key);
      // Sum the values for each category
      const sum = group.reduce((total, entry) => total + Number(entry.value), 0);
      data.push(sum);
    });

    return { data, labels };
  }

  // Enhanced function to process bar chart data
  function processBarChartData(entries: KpiEntry[], chartMode: string = 'time-based', timeUnit: string = 'day', aggregationMethod: string = 'sum', categoryField: string = 'date') {
    if (chartMode === 'time-based') {
      // Use existing time-series aggregation
      const aggregatedEntries = aggregateDataByTimeUnit(entries, timeUnit, aggregationMethod);
      return {
        data: aggregatedEntries.map(entry => Number(entry.value)),
        labels: aggregatedEntries.map(entry => entry.date)
      };
    } else {
      // Use categorical processing
      return processCategoricalData(entries, categoryField);
    }
  }

  // New function to process pie/doughnut chart data with value ranges
  function processPieChartData(entries: KpiEntry[], kpiDetails: Kpi | null, rangeMethod: string = 'target-based', customRanges: any = null) {
    if (!entries || entries.length === 0) return { data: [], labels: [], colors: [], sums: [], counts: [] };

    const values = entries.map(entry => Number(entry.value));
    const maxValue = Math.max(...values);
    const minValue = Math.min(...values);
    const target = kpiDetails ? Number(kpiDetails.target) : null;
    
    let ranges: { label: string; min: number; max: number; color: string }[] = [];
    
    if (rangeMethod === 'target-based' && target && !isNaN(target)) {
      // Target-based ranges
      ranges = [
        { 
          label: `Below Target (₱${minValue}-₱${target - 1})`, 
          min: minValue, 
          max: target - 1, 
          color: '#ef4444' 
        },
        { 
          label: `Within Target (₱${target}+)`, 
          min: target, 
          max: maxValue + 1, 
          color: '#10b981' 
        }
      ];
    } else if (rangeMethod === 'custom' && customRanges) {
      // Enhanced: Auto-adjust custom ranges based on target
      const targetValue = target && !isNaN(target) ? target : maxValue;
      const adjustedRanges = {
        low: customRanges.low || Math.floor(targetValue * 0.2),      // 20% of target
        medium: customRanges.medium || Math.floor(targetValue * 0.6), // 60% of target
        high: customRanges.high || targetValue                       // Target value (default)
      };
      
      ranges = [
        { 
          label: `Low (₱${minValue}-₱${adjustedRanges.low - 1})`, 
          min: minValue, 
          max: adjustedRanges.low - 1, 
          color: '#ef4444' 
        },
        { 
          label: `Medium (₱${adjustedRanges.low}-₱${adjustedRanges.medium - 1})`, 
          min: adjustedRanges.low, 
          max: adjustedRanges.medium - 1, 
          color: '#f59e0b' 
        },
        { 
          label: `High (₱${adjustedRanges.medium}-₱${adjustedRanges.high - 1})`, 
          min: adjustedRanges.medium, 
          max: adjustedRanges.high - 1, 
          color: '#10b981' 
        },
        { 
          label: `Very High (₱${adjustedRanges.high}+)`, 
          min: adjustedRanges.high, 
          max: maxValue + 1, 
          color: '#059669' 
        }
      ];
    } else {
      // Enhanced: Quartile ranges based on target value instead of data
      const targetValue = target && !isNaN(target) ? target : maxValue;
      
      // Calculate quartiles based on target value
      const q1 = Math.floor(targetValue * 0.25);  // 25% of target
      const q2 = Math.floor(targetValue * 0.5);   // 50% of target  
      const q3 = Math.floor(targetValue * 0.75);  // 75% of target
      
      ranges = [
        { 
          label: `Q1 (₱${minValue}-₱${q1 - 1})`, 
          min: minValue, 
          max: q1 - 1, 
          color: '#ef4444' 
        },
        { 
          label: `Q2 (₱${q1}-₱${q2 - 1})`, 
          min: q1, 
          max: q2 - 1, 
          color: '#f59e0b' 
        },
        { 
          label: `Q3 (₱${q2}-₱${q3 - 1})`, 
          min: q2, 
          max: q3 - 1, 
          color: '#10b981' 
        },
        { 
          label: `Q4 (₱${q3}+)`, 
          min: q3, 
          max: maxValue + 1, 
          color: '#059669' 
        }
      ];
    }

    // Count entries and sum values in each range
    const rangeCounts = ranges.map(range => {
      const entriesInRange = entries.filter(entry => {
        const value = Number(entry.value);
        return value >= range.min && value <= range.max;
      });
      
      return {
        label: range.label,
        count: entriesInRange.length,
        sum: entriesInRange.reduce((total, entry) => total + Number(entry.value), 0),
        color: range.color
      };
    });

    // Filter out empty ranges
    const nonEmptyRanges = rangeCounts.filter(range => range.count > 0);

    return {
      data: nonEmptyRanges.map(range => range.count),
      labels: nonEmptyRanges.map(range => range.label),
      colors: nonEmptyRanges.map(range => range.color),
      sums: nonEmptyRanges.map(range => range.sum),
      counts: nonEmptyRanges.map(range => range.count)
    };
  }

  // Load Chart.js on component mount
  onMount(async () => {
    await loadChartJS();
  });

  // Enhanced chart rendering with gap handling
  async function renderChart(canvas: HTMLCanvasElement) {
    if (!canvas || !kpiData) {
      console.log(`Widget ${widget.id}: Cannot render chart - canvas: ${!!canvas}, kpiData: ${!!kpiData}`);
      return;
    }

    // Wait for Chart.js to be loaded
    if (!chartLoaded || !Chart) {
      console.log(`Widget ${widget.id}: Chart.js not loaded yet, waiting...`);
      await loadChartJS();
    }

    try {
      console.log(`Widget ${widget.id}: Rendering chart with data:`, kpiData);

      // Convert kpiData to proper KpiEntry format
      const entries: KpiEntry[] = kpiData!.labels.map((label, index) => ({
        id: index + 1,
        kpi_id: widget.kpi_id,
        date: label,
        value: kpiData!.values[index]
      }));

      let chartData: any;
      let datasets: any[] = [];

      if (widget.type === 'line') {
        // Process data for time-series with gap detection
        const { data: timeSeriesData, gaps, segments } = processTimeSeriesData(
          entries,
          widget.gapThresholdDays || 7,
          widget.timeUnit || 'day',
          widget.aggregationMethod || 'average'
        );

        // Create datasets based on gap handling mode
        const gapHandlingMode = widget.gapHandlingMode || 'broken';
        
        if (segments.length === 0) {
          // No gaps found - single dataset
          datasets = [{
            label: widget.title || `KPI ${widget.kpi_id}`,
            data: timeSeriesData,
            backgroundColor: widget.backgroundColor || 'rgba(54, 162, 235, 0.2)',
            borderColor: widget.borderColor || 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
          }];
        } else {
          // Gaps detected - create separate datasets for each segment (broken lines)
          const sharedLabel = widget.title || `KPI ${widget.kpi_id}`;
          datasets = segments.map((segment, index) => {
            // Calculate date range for this segment
            const segmentDates = segment.map(point => point.x);
            const startDate = new Date(Math.min(...segmentDates));
            const endDate = new Date(Math.max(...segmentDates));
            
            // Format the date range
            const formatDate = (date: Date) => {
              return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
              });
            };
            
            return {
              // Use a shared series label so legend doesn't get cluttered
              label: sharedLabel,
              data: segment,
              backgroundColor: widget.backgroundColor || 'rgba(54, 162, 235, 0.2)',
              borderColor: widget.borderColor || 'rgba(54, 162, 235, 1)',
              borderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
            };
          });
        }
      } else if (widget.type === 'bar') {
        // Process bar chart data
        const barData = processBarChartData(
          entries,
          widget.chartMode || 'time-based',
          widget.timeUnit || 'day',
          widget.aggregationMethod || 'sum',
          widget.categoryField || 'date'
        );

        datasets = [{
          label: widget.title || `KPI ${widget.kpi_id}`,
          data: barData.data,
          backgroundColor: widget.backgroundColor || 'rgba(54, 162, 235, 0.8)',
          borderColor: widget.borderColor || 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }];

        chartData = {
          labels: barData.labels,
          datasets: datasets
        };
      } else if (['pie', 'doughnut'].includes(widget.type)) {
        // Process pie/doughnut chart data with value ranges
        const pieData = processPieChartData(
          entries, 
          kpiDetails, 
          widget.rangeMethod || 'target-based',
          widget.customRanges
        );

        datasets = [{
          label: widget.title || `KPI ${widget.kpi_id}`,
          data: pieData.data,
          backgroundColor: pieData.colors || [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)'
          ],
          borderColor: pieData.colors || [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(255, 205, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
          ],
          borderWidth: 1
        }];

        chartData = {
          labels: pieData.labels,
          datasets: datasets
        };
      } else {
        // For other chart types, use existing logic
        chartData = {
          datasets: [{
            label: widget.title || `KPI ${widget.kpi_id}`,
            data: kpiData!.values,
            backgroundColor: widget.backgroundColor || 'rgba(54, 162, 235, 0.8)',
            borderColor: widget.borderColor || 'rgba(54, 162, 235, 1)',
            borderWidth: 1
          }]
        };
      }

      // For line charts, use the existing chartData structure
      if (widget.type === 'line') {
        chartData = {
          datasets: datasets
        };
      }

      const chartPlugins: any = {
        legend: { 
          display: widget.showLegend !== false,
          position: widget.legendPosition || 'top'
        },
        autocolors: false,
        // Disable datalabels by default (we'll enable only for pie/doughnut)
        datalabels: {
          display: false
        },
        zoom: {
          zoom: {
            wheel: {
              enabled: true,
            },
            pinch: {
              enabled: true
            },
            mode: 'x',
          },
          pan: {
            enabled: true,
            mode: 'x',
          }
        }
      };

      // Tweak legend label styling to reduce truncation on left/right legends
      if (chartPlugins.legend && ['left', 'right'].includes(chartPlugins.legend.position)) {
        // Keep legend vertically centered, just make it more compact to avoid cuts
        chartPlugins.legend.align = 'center';
        chartPlugins.legend.labels = {
          usePointStyle: true,
          pointStyle: 'circle',
          boxWidth: 8,
          boxHeight: 8,
          padding: 6,
          textAlign: 'left',
          font: {
            size: 9
          }
        };
      }

      // For line charts with gap-based multiple datasets, only show a single legend item
      if (widget.type === 'line') {
        const existingLabels = chartPlugins.legend.labels || {};
        chartPlugins.legend.labels = {
          ...existingLabels,
          filter: (legendItem: any, data: any) => {
            // Only keep the first dataset's legend entry
            return legendItem.datasetIndex === 0;
          }
        };
      }

      // Friendly tooltips for line charts
      if (widget.type === 'line') {
        const prefix = kpiDetails?.format_prefix || '';
        const suffix = kpiDetails?.format_suffix || '';
        const unit = widget.timeUnit || 'day';
        const formatDate = (d: Date) => {
          if (unit === 'year') {
            return d.toLocaleDateString('en-US', { year: 'numeric' });
          } else if (unit === 'month') {
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
          } else if (unit === 'week') {
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
          } else {
            // day or default
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
          }
        };

        chartPlugins.tooltip = {
          callbacks: {
            title: (items: any[]) => {
              if (!items || items.length === 0) return '';
              const ts = items[0].parsed?.x;
              if (ts === undefined || ts === null) return '';
              const date = new Date(ts);
              return formatDate(date);
            },
            label: (ctx: any) => {
              const value = typeof ctx.parsed?.y === 'number' ? ctx.parsed.y : Number(ctx.formattedValue);
              const series = ctx.dataset?.label ? `${ctx.dataset.label}: ` : '';
              return `${series}${prefix}${Number(value).toLocaleString()}${suffix}`;
            }
          }
        };
      }

      // Friendly tooltips for bar charts in time-based mode
      if (widget.type === 'bar' && (widget.chartMode || 'time-based') === 'time-based') {
        const prefix = kpiDetails?.format_prefix || '';
        const suffix = kpiDetails?.format_suffix || '';
        const unit = widget.timeUnit || 'day';
        const formatDate = (d: Date) => {
          if (unit === 'year') {
            return d.toLocaleDateString('en-US', { year: 'numeric' });
          } else if (unit === 'month') {
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
          } else if (unit === 'week') {
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
          } else {
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
          }
        };

        chartPlugins.tooltip = {
          callbacks: {
            title: (items: any[]) => {
              if (!items || items.length === 0) return '';
              const xVal = items[0].parsed?.x ?? items[0].label;
              const date = new Date(xVal);
              return isNaN(date.getTime()) ? String(items[0].label) : formatDate(date);
            },
            label: (ctx: any) => {
              const value = typeof ctx.parsed?.y === 'number' ? ctx.parsed.y : Number(ctx.formattedValue);
              const series = ctx.dataset?.label ? `${ctx.dataset.label}: ` : '';
              return `${series}${prefix}${Number(value).toLocaleString()}${suffix}`;
            }
          }
        };
      }

      // Add goal line for line and bar charts if target exists
      if (['line', 'bar'].includes(widget.type)) {
        const targetValue = kpiDetails ? Number(kpiDetails.target) : null;
        if (targetValue !== null && !isNaN(targetValue)) {
          chartPlugins.annotation = {
            annotations: {
              goalLine: {
                type: 'line',
                yMin: targetValue,
                yMax: targetValue,
                borderColor: 'red',
                borderWidth: 2,
                borderDash: [6, 6],
                label: {
                  content: 'Target',
                  enabled: true,
                  position: 'end'
                }
              }
            }
          };
        }
      }

      // Add datalabels for pie/doughnut charts
      if (['pie', 'doughnut'].includes(widget.type)) {
        const pieData = processPieChartData(
          entries, 
          kpiDetails, 
          widget.rangeMethod || 'target-based',
          widget.customRanges
        );
        
        chartPlugins.datalabels = {
          display: widget.datalabelsDisplay !== 'none',
          color: '#fff',
          font: {
            weight: 'bold',
            size: 12
          },
          formatter: (value: number, context: any) => {
            const index = context.dataIndex;
            const counts = pieData.counts || [];
            const sums = pieData.sums || [];
            const count = counts[index] || 0;
            const sum = sums[index] || 0;
            const total = sums.reduce((a: number, b: number) => a + b, 0);
            const percentage = total > 0 ? Math.round((sum / total) * 100) : 0;
            
            // Format based on widget setting
            switch (widget.datalabelsDisplay || 'count-percentage') {
              case 'count-only':
                return `${count}`;
              case 'percentage-only':
                return `${percentage}%`;
              case 'value-only':
                return `₱${sum.toLocaleString()}`;
              case 'value-percentage':
                return `₱${sum.toLocaleString()}\n(${percentage}%)`;
              case 'count-percentage':
              default:
                return `${count}\n(${percentage}%)`;
            }
          },
          anchor: 'center',
          align: 'center'
        };
      }

      chartInstance = new Chart(canvas, {
        type: widget.type,
        data: chartData,
        options: { 
          responsive: true, 
          maintainAspectRatio: ['pie', 'doughnut'].includes(widget.type),
          layout: {
            // Add padding so the footer label doesn't overlap the chart
            padding: {
              top: (chartPlugins.legend && ['top'].includes(chartPlugins.legend.position)) ? 8 : 0,
              bottom: (['pie', 'doughnut'].includes(widget.type) ? 24 : 0) + ((chartPlugins.legend && ['bottom'].includes(chartPlugins.legend.position)) ? 8 : 0),
              left: (chartPlugins.legend && ['left'].includes(chartPlugins.legend.position)) ? 6 : 0,
              right: (chartPlugins.legend && ['right'].includes(chartPlugins.legend.position)) ? 6 : 0
            }
          },
          plugins: chartPlugins,
          scales: ['pie', 'doughnut'].includes(widget.type) ? {} : {
            x: {
              type: (widget.type === 'line') ? 'time' : ((widget.type === 'bar' && (widget.chartMode || 'time-based') === 'time-based') ? 'time' : 'category'),
              time: (widget.type === 'line' || (widget.type === 'bar' && (widget.chartMode || 'time-based') === 'time-based')) ? {
                unit: widget.timeUnit || 'day',
                displayFormats: {
                  day: 'MMM dd',
                  week: 'MMM dd',
                  month: 'MMM yyyy',
                  year: 'yyyy'
                }
              } : undefined,
              title: {
                display: true,
                text: (widget.type === 'line' || (widget.type === 'bar' && (widget.chartMode || 'time-based') === 'time-based')) ? 'Date' : 'Category'
              }
            },
            y: {
              title: {
                display: true,
                text: 'Value'
              }
            }
          },
          interaction: widget.type === 'line' ? {
            // Only show the nearest point's tooltip to avoid multi-segment clutter
            intersect: true,
            mode: 'nearest',
            axis: 'x'
          } : {
            intersect: false,
            mode: 'index'
          }
        }
      });

      console.log(`Widget ${widget.id}: Chart rendered successfully`);
    } catch (chartError) {
      console.error(`Widget ${widget.id}: Chart rendering failed:`, chartError);
      error = `Chart rendering failed: ${chartError instanceof Error ? chartError.message : 'Unknown error'}`;
    }
  }

  onDestroy(() => {
    if (chartInstance) {
      chartInstance.destroy();
    }
  });

  // Async function to handle chart rendering
  async function handleChartRendering() {
    if (!canvasElement) return;
    
    const isChart = ['line', 'bar', 'pie', 'doughnut'].includes(widget.type);

    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }


    // For charts, render when we have data
    if (isChart && kpiData) {
      // For line/bar charts, we prefer to have both data and details (for goal line)
      // but we'll render even without details
      if (['line', 'bar'].includes(widget.type)) {
        if (kpiDetails) {
          await renderChart(canvasElement);
        } else {
          // Render without goal line if details aren't available yet
          await renderChart(canvasElement);
        }
      } else if (['pie', 'doughnut'].includes(widget.type)) {
        // Pie/Doughnut charts don't need details
        await renderChart(canvasElement);
      }
    }
  }

  // Reactive block to trigger chart rendering
  $: if (canvasElement) {
    // Use setTimeout to avoid blocking the reactive update
    setTimeout(() => {
      handleChartRendering();
    }, 0);
  }

</script>

<div class="bg-white rounded-lg shadow-md h-full w-full flex flex-col">
  <div class="p-2 border-b border-gray-200 flex justify-between items-center" on:pointerdown={editMode ? movePointerDown : undefined} class:cursor-move={editMode}>
    <h3 class="font-semibold text-sm text-gray-700 truncate">{widget.title}</h3>
    {#if editMode && canEdit}
      <div class="flex items-center space-x-1">
        {#if isConfirmingDelete}
          <span class="text-xs text-gray-600">Sure?</span>
          <button 
            on:click|stopPropagation={() => { dispatch('remove', { id: widget.id }); isConfirmingDelete = false; }} 
            class="px-2 py-1 text-xs text-white bg-red-600 rounded hover:bg-red-700" 
            aria-label="Confirm Remove"
          >
            Yes
          </button>
          <button 
            on:click|stopPropagation={() => isConfirmingDelete = false} 
            class="px-2 py-1 text-xs text-gray-700 bg-gray-200 rounded hover:bg-gray-300" 
            aria-label="Cancel Remove"
          >
            No
          </button>
        {:else}
          <button 
            on:click={() => dispatch('openSettings', { widget })} 
            class="p-1 text-gray-400 hover:text-gray-700" 
            aria-label="Widget Settings"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
          <button 
            on:click|stopPropagation={() => isConfirmingDelete = true} 
            class="p-1 text-red-400 hover:text-red-700" 
            aria-label="Remove Widget"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        {/if}
      </div>
    {/if}
  </div>
  <!-- svelte-ignore a11y_click_events_have_key_events -->
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <div 
    on:pointerdown={editMode ? movePointerDown : undefined} 
    on:click={handleWidgetClick}
    class:cursor-move={editMode}
    class:cursor-pointer={!editMode && widget.kpi_id && canViewEntries}
    class="flex-grow p-4 min-h-0"
  >
    {#if isLoading}
      <div class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-500">Loading...</p>
        </div>
      </div>
    {:else if error}
      <div class="flex items-center justify-center h-full">
        <div class="text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <p class="mt-2 text-sm text-gray-400 ">{error}</p>
          {#if error.includes('No data available')}
            <p class="mt-1 text-xs text-gray-400">Add KPI entries to see the chart</p>
          {/if}
        </div>
      </div>
    {:else if ['line', 'bar', 'pie', 'doughnut'].includes(widget.type)}
      <div class="w-full h-full relative">
        <!-- Chart Container -->
        <div class="absolute inset-0 flex items-center justify-center">
          <canvas 
            bind:this={canvasElement}
            on:mousedown={handleMouseDown}
            on:mousemove={handleMouseMove}
            on:mouseup={handleMouseUp}
            class="w-full h-full"
          ></canvas>
        </div>
        
        <!-- Chart Info Footer - positioned absolutely at bottom -->
        {#if widget.type === 'line' && widget.timeUnit && widget.aggregationMethod}
          <div class="absolute bottom-0 left-0 right-0 px-2 pb-1 text-xs text-gray-500 text-center bg-white/80 backdrop-blur-sm">
            {widget.timeUnit === 'day' ? 'Daily' : 
             widget.timeUnit === 'week' ? 'Weekly' : 
             widget.timeUnit === 'month' ? 'Monthly' : 
             widget.timeUnit === 'year' ? 'Yearly' : 
             widget.timeUnit.charAt(0).toUpperCase() + widget.timeUnit.slice(1)} • {widget.aggregationMethod.charAt(0).toUpperCase() + widget.aggregationMethod.slice(1)}
          </div>
        {:else if widget.type === 'bar'}
          <div class="absolute bottom-0 left-0 right-0 px-2 pb-1 text-xs text-gray-500 text-center bg-white/80 backdrop-blur-sm">
            {widget.chartMode === 'time-based' ? 
              `${widget.timeUnit === 'day' ? 'Daily' : 
                widget.timeUnit === 'week' ? 'Weekly' : 
                widget.timeUnit === 'month' ? 'Monthly' : 
                widget.timeUnit === 'year' ? 'Yearly' : 
                widget.timeUnit.charAt(0).toUpperCase() + widget.timeUnit.slice(1)} • ${widget.aggregationMethod.charAt(0).toUpperCase() + widget.aggregationMethod.slice(1)}` :
              `Categorical • ${widget.categoryField === 'date' ? 'By Date' : 'By Value Range'}`
            }
          </div>
        {:else if ['pie', 'doughnut'].includes(widget.type)}
          <div class="absolute bottom-0 left-0 right-0 px-2 pb-1 text-xs text-gray-500 text-center bg-white/80 backdrop-blur-sm">
            {widget.rangeMethod === 'target-based' ? 'Target-based Distribution' :
             widget.rangeMethod === 'custom' ? 'Custom Range Distribution' :
             'Quartile-based Distribution'}
          </div>
        {/if}
      </div>
    {:else if widget.type === 'single-value'}
      <div class="flex flex-col items-center justify-center h-full text-center">
        {#if aggregateValue === null}
          <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <p class="mt-2 text-sm text-gray-400">No data available for this KPI.</p>
          </div>
        {:else}
          <h3 class="text-4xl font-bold {getRagClass(aggregateValue, kpiDetails)}">
            {kpiDetails?.format_prefix || ''}{aggregateValue.toLocaleString()}{kpiDetails?.format_suffix || ''}
          </h3>
          <p class="text-sm text-gray-500">{widget.aggregation.charAt(0).toUpperCase() + widget.aggregation.slice(1)} Value</p>
          {#if dateRange}
            <p class="text-xs text-gray-400 mt-1">{getDateRangeText()}</p>
          {/if}
        {/if}
      </div>
    {:else}
      <div class="flex items-center justify-center h-full">
        <p class="text-sm text-gray-500">Unsupported widget type: {widget.type}</p>
      </div>
    {/if}
  </div>
</div>
