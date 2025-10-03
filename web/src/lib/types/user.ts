// User Types
export interface User {
  id: number;
  email: string;
  role: 'admin' | 'editor';
  created_at?: string;
  updated_at?: string;
}
