import eslintConfigPrettier from 'eslint-config-prettier/flat'
import vuetify from 'eslint-config-vuetify'

export default vuetify(

  {
    ts: true,
    rules: {
      'unicorn/no-array-for-each': 'off',
    },
  },
  eslintConfigPrettier,

)