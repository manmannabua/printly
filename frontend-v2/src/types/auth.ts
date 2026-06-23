export interface Permission {
  id: string
  name: string
  display_name: string
  module: string
  description: string | null
  created_at: string
  updated_at: string
}

export interface Role {
  id: string
  name: string
  display_name: string
  description: string | null
  level: number
  created_at: string
  updated_at: string
  permissions?: Permission[]
  permissions_count?: number
  users?: User[]
  users_count?: number
  is_system_role?: boolean
}

export interface EmployeeBasic {
  id: string
  employee_number: string
  first_name: string
  last_name: string
  middle_name: string | null
  status: 'active' | 'on_leave' | 'suspended' | 'terminated'
  profile_photo_url: string | null
  position?: { id: string; title: string } | null
  team?: { id: string; name: string } | null
}

export interface User {
  id: string
  email: string
  brokerage_id?: string | null
  is_active: boolean
  email_verified_at: string | null
  last_login_at: string | null
  created_at: string
  updated_at: string
  roles?: Role[]
  permissions?: string[]
  primary_role?: string
  department_role?: string | null
  role_level?: number
  is_admin?: boolean
  is_team_leader?: boolean
  led_team_ids?: string[]
  has_security_pin?: boolean
  has_permission_overrides?: boolean
  is_locked?: boolean
  employee?: EmployeeBasic | null
}

export interface LoginRequest {
  email: string
  password: string
}

export interface ForgotPasswordRequest {
  email: string
}

export interface ResetPasswordRequest {
  email: string
  password: string
  password_confirmation: string
  token: string
}
