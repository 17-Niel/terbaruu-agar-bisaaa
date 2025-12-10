import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { ThemeProvider } from "./providers/theme-provider";
import { Ziggy } from "./ziggy.js";

createInertiaApp({
    resolve: (name) => {
        // Ambil semua file di folder pages
        const pages = import.meta.glob("./pages/**/*.jsx", { eager: true });
        
        // Buat path yang dicari
        const targetPath = `./pages/${name}.jsx`;

        // --- DEBUG LOG (Cek Console Browser F12) ---
        console.log("------------------------------------------------");
        console.log("1. Controller Request Nama:", name);
        console.log("2. Sistem Mencari File di:", targetPath);
        
        if (pages[targetPath]) {
            console.log("✅ STATUS: FILE DITEMUKAN!");
        } else {
            console.log("❌ STATUS: FILE TIDAK ADA / GAGAL DITEMUKAN");
            console.log("📂 Daftar file yang tersedia di folder 'pages':");
            console.table(Object.keys(pages)); // List semua file yang dideteksi
        }
        console.log("------------------------------------------------");
        // -------------------------------------------

        return pages[targetPath];
    },
    // resolve: (name) => {
    //     const pages = import.meta.glob("./pages/**/*.jsx", { eager: true });
    //     return pages[`./pages/${name}.jsx`];
    // },
    setup({ el, App, props }) {
        // Inject Ziggy routes ke props
        if (props.initialPage.props.ziggy) {
            props.initialPage.props.ziggy = {
                ...Ziggy,
                location: new URL(Ziggy.url).href, // Convert to string
            };
        }

        createRoot(el).render(
            <ThemeProvider>
                <App {...props} />
            </ThemeProvider>
        );
    },
});
