import React, { useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AUTH_TOKEN_KEY } from '@/lib/consts';
import { Toaster, toast } from "sonner"; 

// --- IKON SOSMED ---
const InstagramIcon = ({ className }) => (<svg fill="currentColor" viewBox="0 0 24 24" className={className}><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>);
const EmailIcon = ({ className }) => (<svg fill="currentColor" viewBox="0 0 24 24" className={className}><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>);

export default function LandingLayout({ children, auth, title = "Career Center IT Del", activeMenu = 'home' }) {
    const { flash } = usePage().props;

    // Toast Logic
    useEffect(() => {
        if (flash?.success) toast.success(flash.success, { id: 'flash-success' });
        if (flash?.error) toast.error(flash.error, { id: 'flash-error' });
    }, [flash]);

    // Logout Helper
    useEffect(() => {
        if (!auth) localStorage.removeItem(AUTH_TOKEN_KEY);
    }, [auth]);

    // Menu Helper
    const menus = [
        { label: 'Home', route: 'landing.index', id: 'home' },
        { label: 'Berita', route: '#', id: 'berita' },
        { label: 'Pengumuman', route: '#', id: 'pengumuman' },
        { label: 'Daftar Perusahaan', route: 'landing.perusahaan', id: 'perusahaan' },
        { label: 'Lowongan Pekerjaan', route: 'landing.lowongan', id: 'lowongan' },
        { label: 'Campus Hiring', route: 'landing.campus-hiring', id: 'campus-hiring' },
        { label: 'Tracer Study', route: '#', id: 'tracer' },
        { label: 'User Survey', route: '#', id: 'survey' },
    ];

    return (
        <div className="min-h-screen bg-white font-sans text-slate-800 flex flex-col">
            <Head title={title} />
            <Toaster richColors position="top-center" />

            {/* --- NAVBAR --- */}
            <nav className="bg-white border-b border-gray-200 sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-20 items-center">
                        <div className="flex items-center gap-4">
                            <div className="hidden md:block leading-tight text-sm text-blue-600 font-bold uppercase tracking-wide">
                                Sistem Pusat Pengembangan <br /> Karir dan Hubungan Alumni
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            {auth ? (
                                <>
                                    <span className="hidden md:inline-block text-sm font-medium text-gray-600">
                                        Hi, {auth.nama}
                                    </span>
                                    {auth.akses && auth.akses.includes('Admin') ? (
                                        <Link href="/dashboard" className="bg-[#2F65FF] text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                                            Dashboard
                                        </Link>
                                    ) : (
                                        <Link href={route('auth.logout')} className="bg-red-500 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-red-600 shadow-lg shadow-red-200 transition">
                                            Log Out
                                        </Link>
                                    )}
                                </>
                            ) : (
                                <Link href={route('auth.login')} className="bg-[#2F65FF] text-white px-8 py-2.5 rounded-full text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                                    Sign in
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                <div className="bg-[#4285F4] w-full">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-center gap-8 py-3 overflow-x-auto whitespace-nowrap text-white text-sm font-semibold">
                            {menus.map((item) => (
                                <Link 
                                    key={item.id} 
                                    href={item.route === '#' ? '#' : route(item.route)} 
                                    className={`transition ${activeMenu === item.id ? 'text-yellow-300 underline underline-offset-4' : 'hover:text-blue-100 hover:underline'}`}
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </nav>

            {/* --- CONTENT --- */}
            <div className="flex-grow">
                {children}
            </div>

            {/* --- FOOTER --- */}
            <footer className="bg-[#1e293b] text-white pt-16 pb-8 border-t border-gray-700 mt-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-8 mb-12">
                        {/* Kiri: Logo & Alamat */}
                        <div className="md:col-span-5 space-y-6">
                            <div className="mb-4">
                                <div className="w-24 h-24 bg-white/10 rounded-lg flex items-center justify-center mb-4 overflow-hidden">
                                    {/* LOGO DI-UPDATE DI SINI */}
                                    <img 
                                        src="https://semat.del.ac.id/public/assets/img/Logo%20Institut%20Teknologi%20Del.png" 
                                        alt="Logo IT Del" 
                                        className="max-w-full h-auto p-1" 
                                    />
                                </div>
                                <div className="w-24 h-0.5 bg-gray-500 mb-4"></div>
                                <h4 className="text-xl font-bold mb-4">Institut Teknologi Del</h4>
                                <p className="text-gray-300 text-sm leading-relaxed mb-4">
                                    Jl. Sisingamangaraja, Sitoluama,<br/>
                                    Laguboti, Toba Samosir 22381<br/>
                                    Sumatera Utara, Indonesia
                                </p>
                                <div className="space-y-2 text-sm text-gray-300">
                                    <p>
                                        <strong className="text-white">Customer Service</strong><br/>
                                        Phone & Fax: (+62-632) 331234<br/>
                                        Email: info@del.ac.id
                                    </p>
                                    <p>
                                        <strong className="text-white">Employer Service</strong><br/>
                                        Phone & Fax: (+62-632) 331234<br/>
                                        Email: karir@del.ac.id
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Tengah: Menu Links */}
                        <div className="md:col-span-4">
                             <h4 className="text-lg font-bold mb-6">MENU</h4>
                             <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="space-y-3">
                                    <Link href="#" className="block text-gray-300 hover:text-white transition">Berita</Link>
                                    <Link href="#" className="block text-gray-300 hover:text-white transition">Pengumuman</Link>
                                    <Link href={route('landing.perusahaan')} className="block text-gray-300 hover:text-white transition">Daftar Perusahaan</Link>
                                </div>
                                <div className="space-y-3">
                                    <Link href={route('landing.lowongan')} className="block text-gray-300 hover:text-white transition">Lowongan Pekerjaan</Link>
                                    <Link href="#" className="block text-gray-300 hover:text-white transition">Tracer Study</Link>
                                    <Link href="#" className="block text-gray-300 hover:text-white transition">User Survey</Link>
                                </div>
                             </div>
                        </div>

                        {/* Kanan: Social Media */}
                        <div className="md:col-span-3">
                            <div className="bg-white rounded-2xl p-6 text-slate-900 shadow-lg">
                                <p className="font-bold text-sm mb-4">Temukan Juga Kami di Sosial Media</p>
                                <div className="flex items-center gap-4">
                                    <a href="https://www.instagram.com/karir.itdel/" className="bg-slate-900 text-white p-2 rounded-full hover:bg-slate-700 transition" aria-label="Instagram">
                                        <InstagramIcon className="w-5 h-5" />
                                    </a>
                                    <a href="mailto:karir@del.ac.id" className="bg-slate-900 text-white p-2 rounded-full hover:bg-slate-700 transition" aria-label="Email">
                                        <EmailIcon className="w-5 h-5" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="border-t border-gray-700 pt-8 text-center">
                        <p className="text-sm text-gray-400">© Pusat Pengembangan Karir dan Hubungan Alumni</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}