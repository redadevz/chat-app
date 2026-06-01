import "../bootstrap";
import { createApp, h } from "vue";
import { Link, createInertiaApp } from "@inertiajs/vue3";
import { Notification } from "craftable-pro/Components";
import { AuthenticatedLayout, GuestLayout } from "craftable-pro/Layouts";
import { can } from "craftable-pro/plugins/can";
import {
  i18nVue,
  loadTranslations,
} from "craftable-pro/plugins/laravel-vue-i18n";
import { PageProps } from "craftable-pro/types/page";
import Toast, { POSITION } from "@brackets/vue-toastification";
import "@brackets/vue-toastification/dist/index.css";
import { autoAnimatePlugin } from "@formkit/auto-animate/vue";
import { ZiggyVue } from "ziggy/src/js";

const appName = "Craftable PRO";

const lang = document.documentElement.lang
  ? document.documentElement.lang.replace("-", "_")
  : "en";

createInertiaApp({
  title: (title) => {
    const titleElement = document.querySelector("title");

    if (titleElement && !titleElement.hasAttribute("inertia")) {
      titleElement.remove();
    }

    return title ? `${title} - ${appName}` : appName;
  },
  progress: { color: "#4B5563" },
  resolve: async (name: string) => {
    // Getting all the pages from the craftable-pro package
    const craftableProPagesGlob = import.meta.glob(
      "../../../vendor/brackets/**/resources/js/Pages/**/*.vue",
    );

    // Getting all local pages
    const pagesLocalGlob = import.meta.glob("./Pages/**/*.vue");

    // Resolving all the pages from the craftable-pro package and the local package
    const [pagesCraftablePro, pagesLocal] = await Promise.all([
      craftableProPagesGlob,
      pagesLocalGlob,
    ]);

    // Merging all the pages from the craftable-pro package and the local package with the local ones taking precedence
    const pages = { ...pagesLocal, ...pagesCraftablePro };

    // Finding the page that matches the name
    const pagePath = Object.keys(pages).find((key) =>
      key.endsWith(`/${name}.vue`),
    );

    // Throwing an error if the page is not found
    if (!pagePath) {
      throw new Error(`Page '${name}' not found.`);
    }

    // Getting the page component
    const pageComponent = (await pages[pagePath]()).default;

    const page = pageComponent;

    // Setting the layout if it's not set
    if (page.layout === undefined) {
      if (name.startsWith("Auth/")) {
        page.layout = GuestLayout;
      } else {
        page.layout = AuthenticatedLayout;
      }
    }

    return page;
  },
  setup({ el, App, props, plugin }) {
    loadTranslations(
      `/lang/${
        (props.initialPage.props.auth as PageProps["auth"])?.user?.locale ??
        lang
      }/craftable-pro.json`,
      (translations: JSON) => {
        return createApp({ render: () => h(App, props) })
          .use(plugin)
          .use(Toast, {
            transition: "Vue-Toastification__fade",
            rootComponent: Notification,
            position: POSITION.BOTTOM_RIGHT,
          })
          .use(i18nVue, {
            resolve: (lang: string) => {
              return translations;
            },
          })
          .use(autoAnimatePlugin)
          .use(ZiggyVue)
          .component("Link", Link)
          .directive("can", can)
          .mount(el);
      },
    );
  },
});
