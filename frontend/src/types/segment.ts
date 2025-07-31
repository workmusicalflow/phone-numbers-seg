export interface Segment {
  id: string
  operatorName: string
  operatorCode: string
  countryCode: string
  phoneCount?: number
  createdAt?: string
  updatedAt?: string
}

export interface CustomSegment {
  id: string
  name: string
  pattern: string
  description: string
  phoneCount?: number
  createdAt?: string
  updatedAt?: string
}