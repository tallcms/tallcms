import tailwindcss from '@tailwindcss/postcss';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';

export default {
  plugins: [
    tailwindcss(),
    autoprefixer(),
    ...(process.env.NODE_ENV === 'production' 
      ? [cssnano({ preset: 'default' })]  // 生产环境启用压缩
      : []
    )
  ]
};