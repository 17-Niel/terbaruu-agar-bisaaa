import { AppSidebar } from "@/components/app-sidebar";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import { useTheme } from "@/providers/theme-provider";

import { usePage } from "@inertiajs/react";

import * as Icon from "@tabler/icons-react";
import { Moon, Sun } from "lucide-react";
import { Toaster } from "sonner";
import { route } from "ziggy-js";

export default function AppLayout({ children }) {
    const { auth, appName, pageName } = usePage().props;
    const { theme, colorTheme, toggleTheme, setColorTheme } = useTheme();
    const colorThemes = [
        "blue",
        "green",
        "default",
        "orange",
        "red",
        "rose",
        "violet",
        "yellow",
    ];

    const navData = [
        {
            title: "Main",
            items: [
                {
                    title: "Beranda",
                    url: route("home"),
                    icon: Icon.IconHome,
                },
                {
                    title: "Banner",
                    url: route("banner"),
                    icon: Icon.IconPictureInPictureFilled,
                },
                {
                    title: "Todo",
                    url: route("todo"),
                    icon: Icon.IconChecklist,
                },
                {
                    title: "Perusahaan",
                    url: route("perusahaan"),
                    icon: Icon.IconBuilding,
                },
                {
                    title: "Lowongan Pekerjaan",
                    url: route("lowongan-pekerjaan"),
                    icon: Icon.IconBriefcase, // pilih icon sesuai library kamu
                },
                {
                    title: "Campus Hiring",
                    url: route("campus-hiring"),
                    icon: Icon.IconCertificate,
                },
                {
                    title: "Landingpage",
                    url: route("landing.index"),
                    icon: Icon.IconDeviceDesktopAnalytics,
                },
                {
                    title: "Pengumuman",
                    url: route("pengumuman.index"),
                    icon: Icon.IconSpeakerphone,
                },
                {
                    title: "Berita",
                    url: route("berita.index"),
                    icon: Icon.IconSpeakerphone,
                },
                {
                    title: "Artikel",
                    url: route("artikel"),
                    icon: Icon.IconFile,
                },
            ],
        },
        {
            title: "Admin",
            items: [
                {
                    title: "Hak Akses",
                    url: route("hak-akses"),
                    icon: Icon.IconLock,
                },
            ],
        },
    ];

    return (
        <>
            <SidebarProvider
                style={{
                    "--sidebar-width": "calc(var(--spacing) * 72)",
                    "--header-height": "calc(var(--spacing) * 12)",
                }}
            >
                <AppSidebar
                    active={pageName}
                    user={auth}
                    navData={navData}
                    appName={appName}
                    variant="inset"
                />
                <SidebarInset>
                    <header className="flex h-(--header-height) shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height) sticky top-0 z-50 bg-background/95 backdrop-blur-sm">
                        <div className="flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6">
                            <SidebarTrigger className="-ml-1" />
                            <Separator
                                orientation="vertical"
                                className="mx-2 data-[orientation=vertical]:h-4"
                            />
                            <h1 className="text-base font-medium">
                                {pageName}
                            </h1>
                            <div className="ml-auto flex items-center gap-2">
                                <Select
                                    className="capitalize"
                                    value={colorTheme}
                                    onValueChange={setColorTheme}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Tema" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>Tema</SelectLabel>
                                            {colorThemes.map((item) => (
                                                <SelectItem
                                                    key={`theme-${item}`}
                                                    value={item}
                                                >
                                                    {item}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>

                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={toggleTheme}
                                >
                                    {theme === "light" ? (
                                        <Sun className="h-4 w-4" />
                                    ) : (
                                        <Moon className="h-4 w-4" />
                                    )}
                                </Button>
                            </div>
                        </div>
                    </header>
                    <div className="flex flex-1 flex-col">
                        <div className="@container/main flex flex-1 flex-col gap-2">
                            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6 px-4 md:px-6">
                                {children}
                            </div>
                        </div>
                    </div>
                </SidebarInset>
            </SidebarProvider>
            <Toaster richColors position="top-center" />
        </>
    );
}
