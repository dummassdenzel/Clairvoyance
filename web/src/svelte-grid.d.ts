declare module 'svelte-grid' {
    import { SvelteComponentTyped } from 'svelte';
    
    export interface GridItem {
      id: string;
      x: number;
      y: number;
      w: number;
      h: number;
      fixed?: boolean;
      resizable?: boolean;
      draggable?: boolean;
      customDragger?: boolean;
      customResizer?: boolean;
      min?: { w: number; h: number };
      max?: { w: number; h: number };
    }
    
    export interface GridProps {
      items: GridItem[];
      rowHeight?: number;
      cols?: number;
      gap?: number[];
      fastStart?: boolean;
      threshold?: number;
      verticalCompact?: boolean;
      fillSpace?: boolean;
      sensor?: number;
    }
    
    export default class Grid extends SvelteComponentTyped<GridProps> {}
  }