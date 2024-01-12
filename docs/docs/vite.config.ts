import {defineConfig} from 'vite'
import { sitemap, Url } from '@aminnairi/rollup-plugin-sitemap'
import VitePressConfig from './.vitepress/config'
import {DefaultTheme} from "vitepress/types/default-theme";

const docsSiteBaseUrl = 'https://nystudio107.com'
const docsBaseUrl = new URL(VitePressConfig.base!, docsSiteBaseUrl).href.replace(/\/$/, '') + '/';
let siteMapUrls: Url[] = [];
if (Array.isArray(VitePressConfig.themeConfig?.sidebar)) {
  siteMapUrls = VitePressConfig.themeConfig?.sidebar?.map((group: DefaultTheme.SidebarItem) => {
    return group.items!.map((items: DefaultTheme.SidebarItem) => (<Url>{
      location: items.link!.replace(/^\/+/, '') ?? '',
      lastModified: new Date(),
    }));
  }).reduce((prev: Url[], curr: Url[]) => {
    return prev!.concat(curr!);
  });
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    sitemap({
      baseUrl: docsBaseUrl,
      urls: siteMapUrls,
    })
  ],
  server: {
    host: '0.0.0.0',
    port: parseInt(process.env.DOCS_DEV_PORT ?? '4000'),
    strictPort: true,
  }
});
