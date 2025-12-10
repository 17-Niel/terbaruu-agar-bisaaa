import * as React from "react";

import { NavUser } from "@/components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { cn } from "@/lib/utils";

interface AppSidebarProps extends React.ComponentProps<typeof Sidebar> {
    active?: string;
    user: {
        name: string;
        username: string;
        photo: string;
    };
    appName: string;
    navData: {
        title: string;
        items: {
            title: string;
            url: string;
            icon: React.ElementType;
        }[];
    }[];
}

export function AppSidebar({
    active = "",
    user,
    appName,
    navData,
    ...props
}: AppSidebarProps) {
    return (
        <Sidebar collapsible="offcanvas" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            className="data-[slot=sidebar-menu-button]:p-1.5!"
                        >
                            <a href="#">
                                <img
                                    src="/img/logo/sdi-logo-dark.png"
                                    alt="ITDel Logo"
                                    className="w-6 block dark:hidden"
                                />
                                <img
                                    src="/img/logo/sdi-logo-light.png"
                                    alt="ITDel Logo"
                                    className="w-6 hidden dark:block"
                                />
                                <span>{appName}</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                <SidebarGroup className="group-data-[collapsible=icon]:hidden">
                    {/* Navigation */}
                    <div className="mb-1">
                        {navData.map((navGroup) => (
                            <div className="mb-2" key={`nav-${navGroup.title}`}>
                                <SidebarGroupLabel>
                                    {navGroup.title}
                                </SidebarGroupLabel>
                                <SidebarMenu>
                                    {navGroup.items.map((item) => (
                                        <SidebarMenuItem key={item.title}>
                                            <SidebarMenuButton
                                                asChild
                                                className={cn(
                                                    "hover:bg-primary/5 hover:text-primary",
                                                    {
                                                        "bg-primary/5":
                                                            active.startsWith(
                                                                item.title
                                                            ),
                                                        "text-primary":
                                                            active.startsWith(
                                                                item.title
                                                            ),
                                                        "border-l border-primary":
                                                            active.startsWith(
                                                                item.title
                                                            ),
                                                    }
                                                )}
                                            >
                                                <a href={item.url}>
                                                    <item.icon />
                                                    <span>{item.title}</span>
                                                </a>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </div>
                        ))}
                    </div>
                </SidebarGroup>
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={user} />
            </SidebarFooter>
        </Sidebar>
    );
}
