type TCountriesData = {
  name: string
  id: string
  currency: string
  originalPrice: number
  value: number
}

type TTooltip = {
  data: TCountriesData
}

type TSummaryItem = {
  title: string
  rel: string
  currency: string
  countryCode: string
  region: string
  restoredPrice: number
  price: number
  f_price: number
  convertedPrice: number
}