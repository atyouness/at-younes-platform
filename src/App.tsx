/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Play,
  Volume2,
  FileText,
  CheckSquare,
  ExternalLink,
  Headphones,
  Sparkles,
  RotateCcw,
  Building2,
  ShieldCheck,
  Scale,
  HelpCircle,
  Clock,
  BookOpen,
  ArrowRight,
  ShieldAlert,
  ChevronLeft
} from 'lucide-react';

// Data types
interface DocItem {
  id: string;
  title: string;
  url: string;
  icon: React.ComponentType<any>;
  badge?: string;
  isForm?: boolean;
}

interface AudioItem {
  id: string;
  title: string;
  url: string;
  description: string;
  duration: string;
}

export default function App() {
  // Main background image ID from Google Drive
  const driveImageId = '1cnZ8TOSyZSEGDS1sd1QoCkyfLGtbVnLm';
  const directImageUrl = `https://lh3.googleusercontent.com/d/${driveImageId}`;
  const backupImageUrl = `https://drive.google.com/uc?export=view&id=${driveImageId}`;

  // State for tracking active selected item
  const [selectedItem, setSelectedItem] = useState<DocItem | AudioItem | null>(null);
  const [selectedType, setSelectedType] = useState<'document' | 'form' | 'audio' | null>(null);
  const [animationKey, setAnimationKey] = useState<number>(0);
  const [isAudioMuted, setIsAudioMuted] = useState<boolean>(false);

  // List of standard documents & forms
  const documentList: DocItem[] = [
    {
      id: 'form',
      title: '📝 اضغط لملء استمارة الانضمام',
      url: 'https://docs.google.com/forms/d/e/1FAIpQLScpWXz0O4E67jUsykDGtdrBwxyf3ImwR6y2dFhnGAuTNKRVXw/viewform?embedded=true',
      icon: CheckSquare,
      badge: 'هام وجديد',
      isForm: true
    },
    {
      id: 'doc1',
      title: '📖 الملف 1: العرض التوضيحي للمشروع',
      url: 'https://docs.google.com/document/d/1wICU_ijY1kWZ47ZHR469PmXmg7KSpsfA/preview',
      icon: BookOpen,
    },
    {
      id: 'doc2',
      title: '📖 الملف 2: الورقة البيضاء',
      url: 'https://docs.google.com/document/d/11lRmnT42bY07N8Qn-pfSCOPU7MF-9fFv/preview',
      icon: FileText,
    },
    {
      id: 'doc3',
      title: '📖 الملف 3: دليل خطة دخل العمولات',
      url: 'https://docs.google.com/document/d/1BPPaXzYSRnBaAyIZD3QO-cfnjqAvEiq_/preview',
      icon: ShieldCheck,
      badge: 'محدث'
    },
    {
      id: 'doc4',
      title: '📖 الملف 4: التغطية الضريبية والامتثال',
      url: 'https://docs.google.com/document/d/1cdfMsIX3mpLqfPU8XwbVmd486RLjvaz2/preview',
      icon: Scale,
    },
    {
      id: 'doc5',
      title: '📖 الملف 5: سرية البيانات وحماية الملكية',
      url: 'https://docs.google.com/document/d/1RMNqyM8tuRw3KlQut-4NDAXoV6mItF95/preview',
      icon: ShieldCheck,
    },
    {
      id: 'doc6',
      title: '📖 الملف 6: القوة القاهرة وإخلاء المسؤولية',
      url: 'https://docs.google.com/document/d/1SB7_kOOmvxupxdjE4OscNYiTMB5zGvb1/preview',
      icon: ShieldAlert,
    },
    {
      id: 'doc7',
      title: '📖 الملف 7: آلية حل النزاعات',
      url: 'https://docs.google.com/document/d/1qpPrD-wUoand0bDamKxSj9Ud0KvWJMav/preview',
      icon: Scale,
    }
  ];

  // List of the 5 audio recordings
  const audioList: AudioItem[] = [
    {
      id: 'audio1',
      title: '1_ مقدمة 1',
      url: 'https://drive.google.com/file/d/1QKa4gIpgkIkX6JNnA9cqZzKIJFU32qc_/preview',
      description: 'مقدمة عامة حول فكرة ورؤية المشروع السكني والتقني التأسيسي.',
      duration: '4:12'
    },
    {
      id: 'audio2',
      title: '2_ مقدمة 2',
      url: 'https://drive.google.com/file/d/1LMg_0QMfQoxOdD-vskos5Md6BKSb_v7O/preview',
      description: 'استكمال الشرح التمهيدي، الأركان الاستراتيجية وآلية الانطلاق.',
      duration: '3:45'
    },
    {
      id: 'audio3',
      title: '3_ ملء استشارة الانضمام 1',
      url: 'https://drive.google.com/file/d/1b2LhW8fQ5tvMQq4Q3qVIBDDcKGWlLtpu/preview',
      description: 'دليل تفصيلي خطوة بخطوة لملء استمارة المشاركة وتقديم البيانات.',
      duration: '5:20'
    },
    {
      id: 'audio4',
      title: '4_ ملء استشارة الانضمام 2',
      url: 'https://drive.google.com/file/d/1PtdqnU7HrfS2Ch4WmnIwoePD4Q22NByJ/preview',
      description: 'توضيح أهم الحقول والشروط القانونية المطلوبة للمؤسسين الجدد.',
      duration: '2:50'
    },
    {
      id: 'audio5',
      title: '5_ ملء استشارة الانضمام 3',
      url: 'https://drive.google.com/file/d/1C1uN3_aVEBjAbsFlP9nphK402u7ouEQR/preview',
      description: 'خاتمة ونصائح ذهبية لضمان قبول طلب العضوية وتأكيد ريادتك.',
      duration: '4:05'
    }
  ];

  // Handle selecting standard items
  const handleSelectDoc = (item: DocItem) => {
    setSelectedItem(item);
    setSelectedType(item.isForm ? 'form' : 'document');
    // Scroll smoothly to viewer
    const viewerElement = document.getElementById('viewer-section');
    if (viewerElement) {
      viewerElement.scrollIntoView({ behavior: 'smooth' });
    }
  };

  // Handle selecting audio items (triggers the 4-direction animation)
  const handleSelectAudio = (item: AudioItem) => {
    setSelectedItem(item);
    setSelectedType('audio');
    // Increment key to restart the Framer Motion animation sequence
    setAnimationKey(prev => prev + 1);
    
    // Scroll smoothly to viewer
    const viewerElement = document.getElementById('viewer-section');
    if (viewerElement) {
      viewerElement.scrollIntoView({ behavior: 'smooth' });
    }
  };

  // Re-run the beautiful 4-direction animation
  const handleReplayAnimation = () => {
    setAnimationKey(prev => prev + 1);
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-800 pb-12 antialiased selection:bg-indigo-600 selection:text-white" dir="rtl">
      {/* Decorative background grid and ambient lighting */}
      <div className="absolute top-0 right-0 left-0 h-[600px] bg-gradient-to-b from-indigo-50/50 to-transparent -z-10" />
      <div className="absolute top-12 right-1/4 w-96 h-96 bg-blue-400/10 rounded-full filter blur-3xl -z-10" />
      <div className="absolute top-24 left-1/4 w-96 h-96 bg-indigo-400/10 rounded-full filter blur-3xl -z-10" />

      <div className="max-w-5xl mx-auto px-4 sm:px-6 pt-8">
        
        {/* Main Card Wrapper */}
        <div id="main-card-wrapper" className="bg-white rounded-2xl border border-slate-100 shadow-xl overflow-hidden">
          
          {/* Main Card Header with Premium Real Estate theme */}
          <div className="relative bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 text-white p-8 sm:p-12 overflow-hidden">
            {/* Ambient image background overlay with high blend mode */}
            <div 
              className="absolute inset-0 bg-cover bg-center opacity-10 mix-blend-overlay scale-110"
              style={{ backgroundImage: `url(${directImageUrl})` }}
            />
            
            <div className="relative z-10">
              <div className="inline-flex items-center gap-2 px-3 py-1 bg-amber-500/20 border border-amber-500/30 text-amber-300 rounded-full text-xs font-bold mb-4 shadow-sm">
                <Sparkles className="w-3.5 h-3.5 animate-pulse" />
                <span>فرصة حصرية للمؤسسين</span>
              </div>
              
              <h1 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight mb-4">
                🏢 مشروع المرادية السكني الترقوي الفاخر
              </h1>
              
              <p className="text-lg text-slate-200 font-medium leading-relaxed max-w-4xl mb-6">
                بصفتنا الوكيل الحصري لتسويق مشروع المرادية السكني الترقوي الفاخر بقلب العاصمة، نتشرف بدعوتكم لقطع أول خطوة نحو الريادة المالية وبناء شبكتكم التسويقية المحترفة، لتكونوا ضمن أول 100 مؤسس، ولمدة محدودة آخر أجل <span className="text-amber-400 font-extrabold underline decoration-wavy decoration-amber-400">10 جويلية 2026</span>.
              </p>

              <div className="h-[1px] bg-white/10 my-6" />

              <div className="p-4 rounded-xl bg-white/5 border border-white/10 backdrop-blur-md max-w-3xl">
                <p className="text-slate-300 text-sm leading-relaxed">
                  هل تريد أن تكون جزءًا من أول فريق يضع حجر الأساس لمشروع شركتنا <strong className="text-white">"آت يونس للتكنولوجيا والحلول الرقمية" (EURL)</strong>، وبالمختصر المفيد <span className="text-amber-300 font-bold">آت يونس تك (At Yunes Tech)</span> العملاق؟ هل لديك رغبة في العمل الميداني، التسويق، وبناء شبكة المشروع؟
                </p>
              </div>
            </div>
          </div>

          {/* Core Strategic Pillars Block */}
          <div className="p-6 sm:p-8 bg-slate-50 border-b border-slate-100">
            <h3 className="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
              <Building2 className="w-5 h-5 text-indigo-600" />
              <span>🚀 فرصة استثمارية وترويجية فريدة تجمع بين ثلاثة أركان استراتيجية:</span>
            </h3>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {/* Pillar 1 */}
              <div className="bg-white p-5 rounded-xl border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                <div className="w-10 h-10 bg-blue-50 text-blue-700 rounded-lg flex items-center justify-center font-black mb-4">🏢</div>
                <h4 className="font-bold text-slate-900 mb-2">مشروع عقاري متين</h4>
                <p className="text-slate-600 text-sm leading-relaxed">
                  مشروع سكني ترقوي ضخم بقلب العاصمة (المرادية) يضمن لك الاستقرار الاستثماري العيني والنمو طويل الأمد.
                </p>
              </div>

              {/* Pillar 2 */}
              <div className="bg-white p-5 rounded-xl border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                <div className="w-10 h-10 bg-emerald-50 text-emerald-700 rounded-lg flex items-center justify-center font-black mb-4">📈</div>
                <h4 className="font-bold text-slate-900 mb-2">مؤسسة مبتكرة (Start-up)</h4>
                <p className="text-slate-600 text-sm leading-relaxed">
                  تأسيس شركة خدمات وحلول رقمية رائدة سيتم السعي لإدراجها في بورصة الجزائر لتعظيم العوائد.
                </p>
              </div>

              {/* Pillar 3 */}
              <div className="bg-white p-5 rounded-xl border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                <div className="w-10 h-10 bg-indigo-50 text-indigo-700 rounded-lg flex items-center justify-center font-black mb-4">👥</div>
                <h4 className="font-bold text-slate-900 mb-2">شبكة قيادية ريادية</h4>
                <p className="text-slate-600 text-sm leading-relaxed">
                  بناء شبكة وطنية من المروجين والوكلاء المعتمدين، تكونوا أنتم نواتها الأولى وقادتها المؤسسين.
                </p>
              </div>
            </div>

            <p className="mt-6 text-indigo-900 font-bold text-center bg-indigo-50/70 p-3 rounded-lg border border-indigo-100 text-sm">
              ✨ انضم الآن إلى مؤسسينا في آت يونس تك (At Yunes Tech) وكن شريكًا في بناء أكبر منصة استثمارية ترويجية في المنطقة.
            </p>
          </div>

          {/* Document Section */}
          <div className="p-6 sm:p-8">
            <div className="mb-6">
              <h3 className="text-xl font-black text-slate-900 flex items-center gap-2 mb-2">
                <FileText className="w-5.5 h-5.5 text-blue-900" />
                <span>📑 اختر الإجراء أو الملف الذي ترغب في تصفحه:</span>
              </h3>
              <p className="text-sm text-slate-500">انقر على أي ملف أو وثيقة لفتحها مباشرة في نافذة الاستعراض بالأسفل.</p>
            </div>

            {/* List of Document buttons with improved layout */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-8">
              {documentList.map((doc) => {
                const IconComponent = doc.icon;
                const isSelected = selectedItem?.id === doc.id;
                
                return (
                  <button
                    key={doc.id}
                    onClick={() => handleSelectDoc(doc)}
                    id={`btn-doc-${doc.id}`}
                    className={`flex items-start text-right p-4 rounded-xl border transition-all duration-200 text-sm font-bold relative group overflow-hidden ${
                      doc.isForm
                        ? isSelected
                          ? 'bg-emerald-600 text-white border-emerald-600 shadow-emerald-200 shadow-md'
                          : 'bg-emerald-50 text-emerald-900 border-emerald-200/80 hover:bg-emerald-100/80 hover:border-emerald-300'
                        : isSelected
                          ? 'bg-slate-900 text-white border-slate-900 shadow-md'
                          : 'bg-white text-slate-800 border-slate-200 hover:bg-slate-50 hover:border-slate-300'
                    }`}
                  >
                    {doc.badge && (
                      <span className={`absolute top-0 left-0 px-2 py-0.5 rounded-br-lg text-[10px] font-extrabold ${
                        doc.isForm || isSelected ? 'bg-amber-400 text-slate-900' : 'bg-indigo-100 text-indigo-800'
                      }`}>
                        {doc.badge}
                      </span>
                    )}
                    
                    <div className="flex gap-3 items-center w-full">
                      <div className={`p-2 rounded-lg shrink-0 ${
                        isSelected 
                          ? 'bg-white/10 text-white' 
                          : doc.isForm 
                            ? 'bg-emerald-500/10 text-emerald-700' 
                            : 'bg-slate-100 text-slate-600'
                      }`}>
                        <IconComponent className="w-5 h-5" />
                      </div>
                      <div className="flex-1 min-w-0 pr-1">
                        <p className="truncate leading-normal">{doc.title.replace(/📝\s*|📖\s*/, '')}</p>
                        <p className={`text-[11px] mt-1 font-medium ${
                          isSelected ? 'text-slate-300' : doc.isForm ? 'text-emerald-700/80' : 'text-slate-400'
                        }`}>
                          {doc.isForm ? 'تعبئة استمارة التقديم الرسمية' : 'تصفح وقراءة المستند'}
                        </p>
                      </div>
                    </div>
                  </button>
                );
              })}
            </div>

            <div className="h-[1px] bg-slate-200/80 my-8" />

            {/* AUDIO RECORDINGS SECTION WITH PREMIUM STYLE AND UNIQUE BACKGROUNDS */}
            <div className="mb-6">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                <h3 className="text-xl font-black text-slate-900 flex items-center gap-2">
                  <Headphones className="w-5.5 h-5.5 text-blue-900 animate-bounce" />
                  <span>🎙️ تسجيلات صوتية لشرح مشروع تأسيس مؤسسة استثمارية ترويجية ناشئة:</span>
                </h3>
                <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-950 font-bold rounded-full text-xs self-start sm:self-auto border border-amber-200">
                  <Sparkles className="w-3.5 h-3.5 text-amber-600" />
                  <span>تفعّل الخلفية المتحركة عند النقر</span>
                </span>
              </div>
              <p className="text-sm text-slate-500">
                انقر على أحد التسجيلات الصوتية الخمسة لتفعيل المشغل الصوتي المتقدم في الإطار بالأسفل مع <strong className="text-indigo-900">خلفية الصورة المتحركة من 4 اتجاهات</strong>.
              </p>
            </div>

            {/* 5 Audio Cards Grid (Satisfying: "تحسين مظهر لخمسة تسجيلات الصوتية وإجعل هذه الصورة خلفية لكل تسجيل صوتي") */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {audioList.map((audio) => {
                const isSelected = selectedItem?.id === audio.id;
                
                return (
                  <div
                    key={audio.id}
                    id={`audio-card-${audio.id}`}
                    onClick={() => handleSelectAudio(audio)}
                    className={`relative p-5 rounded-2xl border transition-all duration-300 cursor-pointer overflow-hidden group select-none ${
                      isSelected
                        ? 'border-indigo-600 ring-2 ring-indigo-500/20 shadow-lg'
                        : 'border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300'
                    }`}
                  >
                    {/* The requested Google Drive Image used as background for each audio card */}
                    <div 
                      className="absolute inset-0 bg-cover bg-center transition-all duration-500 opacity-15 group-hover:scale-110"
                      style={{ backgroundImage: `url(${directImageUrl})` }}
                    />
                    
                    {/* Visual overlay for reading protection and gorgeous styling */}
                    <div className={`absolute inset-0 transition-colors duration-300 ${
                      isSelected 
                        ? 'bg-gradient-to-br from-indigo-950/95 via-slate-900/90 to-blue-950/95 text-white' 
                        : 'bg-gradient-to-br from-white/90 via-slate-50/95 to-white/90 text-slate-800'
                    }`} />

                    {/* Content Layer */}
                    <div className="relative z-10 flex flex-col h-full justify-between">
                      <div>
                        {/* Audio Badge & Duration */}
                        <div className="flex items-center justify-between mb-3">
                          <span className={`px-2.5 py-1 rounded-full text-[11px] font-bold ${
                            isSelected 
                              ? 'bg-indigo-500/30 text-indigo-200 border border-indigo-400/20' 
                              : 'bg-indigo-50 text-indigo-700 border border-indigo-100'
                          }`}>
                            تسجيل صوتي تفسيري
                          </span>
                          <span className={`text-[11px] font-bold flex items-center gap-1 ${
                            isSelected ? 'text-slate-300' : 'text-slate-400'
                          }`}>
                            <Clock className="w-3.5 h-3.5" />
                            {audio.duration}
                          </span>
                        </div>

                        {/* Title */}
                        <h4 className="text-base font-extrabold mb-1 group-hover:text-indigo-600 transition-colors line-clamp-1">
                          {audio.title}
                        </h4>

                        {/* Description */}
                        <p className={`text-xs font-medium leading-relaxed mb-4 line-clamp-2 ${
                          isSelected ? 'text-slate-300' : 'text-slate-500'
                        }`}>
                          {audio.description}
                        </p>
                      </div>

                      {/* Card Footer action button */}
                      <div className="flex items-center justify-between pt-2 border-t border-slate-100/10">
                        {isSelected ? (
                          <div className="flex items-center gap-1.5 text-amber-400 text-xs font-bold">
                            <span className="flex gap-0.5 items-end h-3">
                              <span className="w-0.75 bg-amber-400 animate-[bounce_0.8s_infinite_100ms] h-full" />
                              <span className="w-0.75 bg-amber-400 animate-[bounce_0.8s_infinite_200ms] h-2/3" />
                              <span className="w-0.75 bg-amber-400 animate-[bounce_0.8s_infinite_300ms] h-full" />
                            </span>
                            <span>نشط الآن</span>
                          </div>
                        ) : (
                          <span className="text-xs text-slate-400 group-hover:text-indigo-600 font-bold transition-colors">
                            اضغط للاستماع والتشغيل
                          </span>
                        )}

                        <div className={`w-8 h-8 rounded-full flex items-center justify-center transition-all ${
                          isSelected 
                            ? 'bg-amber-400 text-slate-900 rotate-12 scale-110' 
                            : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white group-hover:scale-105'
                        }`}>
                          <Play className="w-4 h-4 fill-current translate-x-[-1px]" />
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Separating Line with subtle styling */}
          <div className="px-6 sm:p-8">
            <div className="relative flex py-5 items-center">
              <div className="flex-grow border-t border-slate-200"></div>
              <span className="flex-shrink mx-4 text-slate-400 text-xs font-bold px-3 py-1 bg-slate-100 rounded-full flex items-center gap-1.5">
                <Building2 className="w-3.5 h-3.5" />
                آت يونس تك للحلول الرقمية
              </span>
              <div className="flex-grow border-t border-slate-200"></div>
            </div>
          </div>

          {/* ACTIVE VIEWER CONTAINER (Satisfying: "أريد أن يظل الإطار فارغاً في البداية، وتظهر الصورة فقط عند الضغط...") */}
          <div id="viewer-section" className="p-6 sm:p-8 bg-slate-50 border-t border-slate-100 scroll-mt-6">
            <div className="text-center font-bold text-slate-500 mb-4 text-sm flex items-center justify-center gap-2">
              <div className="w-2 h-2 rounded-full bg-indigo-600 animate-ping" />
              <span>👇 سيظهر محتوى الملف أو الاستمارة المختار هنا بالأسفل</span>
            </div>

            <div className="relative w-full rounded-2xl border border-slate-200/80 bg-slate-100 overflow-hidden shadow-inner min-h-[500px] lg:min-h-[700px] flex flex-col">
              
              <AnimatePresence mode="wait">
                {/* 1. INITIAL STATE: Fully empty and elegant placeholder (Satisfying: "يظل الإطار فارغاً في البداية") */}
                {!selectedItem && (
                  <motion.div
                    key="empty-state"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-white"
                  >
                    <div className="relative mb-6">
                      <div className="absolute inset-0 bg-blue-500/10 rounded-full filter blur-xl scale-125 animate-pulse" />
                      <div className="w-20 h-20 bg-gradient-to-tr from-slate-900 to-indigo-950 text-white rounded-2xl flex items-center justify-center shadow-lg transform rotate-3">
                        <Building2 className="w-10 h-10 text-amber-400" />
                      </div>
                    </div>
                    
                    <h3 className="text-xl font-extrabold text-slate-800 mb-2">مرحباً بك في منصة مشروع المرادية السكني</h3>
                    <p className="text-slate-500 text-sm max-w-md leading-relaxed mb-6">
                      للبدء في تصفح مستندات المشروع القانونية، خطة العمل، استمارة الانضمام، أو الاستماع للتسجيلات الصوتية التفسيرية، يرجى اختيار أحد الملفات المتاحة أعلاه.
                    </p>
                    
                    <div className="flex flex-wrap gap-3 justify-center">
                      <div className="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200/50">
                        🛡️ وثائق مؤمنة بالكامل
                      </div>
                      <div className="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200/50">
                        ⚡ مشغل صوتي تفاعلي
                      </div>
                      <div className="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200/50">
                        📝 استمارة مباشرة
                      </div>
                    </div>
                  </motion.div>
                )}

                {/* 2. AUDIO VIEWER: Shows the image with a breathtaking 4-direction animation overlaying the player */}
                {selectedItem && selectedType === 'audio' && (
                  <motion.div
                    key={`audio-viewer-${selectedItem.id}-${animationKey}`}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="absolute inset-0 flex flex-col bg-slate-950 text-white overflow-hidden"
                  >
                    {/* FOUR DIRECTION ANIMATION PANELS (Satisfying: "وأريد صورة متحركة ومن اربع جهات") */}
                    {/* These 4 layers containing the Google Drive image slide in from the 4 cardinal directions and merge */}
                    <div className="absolute inset-0 z-0 pointer-events-none overflow-hidden">
                      {/* 1. TOP PANEL: Slides from the Top */}
                      <motion.div
                        initial={{ y: '-100%', opacity: 0 }}
                        animate={{ y: '0%', opacity: 0.25 }}
                        transition={{ type: 'spring', damping: 20, stiffness: 60, delay: 0.1 }}
                        className="absolute inset-x-0 top-0 h-1/2 bg-cover bg-center"
                        style={{ backgroundImage: `url(${directImageUrl})`, clipPath: 'polygon(0 0, 100% 0, 100% 100%, 0 50%)' }}
                      />

                      {/* 2. BOTTOM PANEL: Slides from the Bottom */}
                      <motion.div
                        initial={{ y: '100%', opacity: 0 }}
                        animate={{ y: '0%', opacity: 0.25 }}
                        transition={{ type: 'spring', damping: 20, stiffness: 60, delay: 0.2 }}
                        className="absolute inset-x-0 bottom-0 h-1/2 bg-cover bg-center"
                        style={{ backgroundImage: `url(${directImageUrl})`, clipPath: 'polygon(0 50%, 100% 0, 100% 100%, 0 100%)' }}
                      />

                      {/* 3. LEFT PANEL: Slides from the Left */}
                      <motion.div
                        initial={{ x: '-100%', opacity: 0 }}
                        animate={{ x: '0%', opacity: 0.25 }}
                        transition={{ type: 'spring', damping: 20, stiffness: 60, delay: 0.3 }}
                        className="absolute inset-y-0 left-0 w-1/2 bg-cover bg-center"
                        style={{ backgroundImage: `url(${directImageUrl})`, clipPath: 'polygon(0 0, 100% 0, 50% 100%, 0 100%)' }}
                      />

                      {/* 4. RIGHT PANEL: Slides from the Right */}
                      <motion.div
                        initial={{ x: '100%', opacity: 0 }}
                        animate={{ x: '0%', opacity: 0.25 }}
                        transition={{ type: 'spring', damping: 20, stiffness: 60, delay: 0.4 }}
                        className="absolute inset-y-0 right-0 w-1/2 bg-cover bg-center"
                        style={{ backgroundImage: `url(${directImageUrl})`, clipPath: 'polygon(50% 0, 100% 0, 100% 100%, 0 100%)' }}
                      />

                      {/* CENTRAL MERGED FULL-SCREEN ACCENT BACKGROUND */}
                      <motion.div
                        initial={{ scale: 1.1, opacity: 0 }}
                        animate={{ scale: 1, opacity: 0.4 }}
                        transition={{ duration: 1.2, delay: 0.8 }}
                        className="absolute inset-0 bg-cover bg-center mix-blend-screen filter blur-[2px]"
                        style={{ backgroundImage: `url(${directImageUrl})` }}
                      />

                      {/* Glassmorphism Dark Vignette Overlay */}
                      <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-slate-950" />
                    </div>

                    {/* Interactive Custom Audio Player Layer */}
                    <div className="relative z-10 flex flex-col items-center justify-between h-full p-6 sm:p-10 flex-1">
                      
                      {/* Top Bar inside Player */}
                      <div className="w-full flex justify-between items-center bg-white/5 p-3 rounded-xl border border-white/10 backdrop-blur-md">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-full bg-indigo-600/30 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                            <Headphones className="w-5 h-5 animate-pulse" />
                          </div>
                          <div className="text-right">
                            <span className="text-[10px] text-indigo-300 font-extrabold uppercase tracking-wide block">مشروع المرادية الترقوي</span>
                            <span className="text-sm font-bold text-white block truncate max-w-[200px] sm:max-w-md">{selectedItem.title}</span>
                          </div>
                        </div>

                        {/* Interactive animation controller & replay */}
                        <div className="flex items-center gap-2">
                          <button
                            onClick={handleReplayAnimation}
                            title="إعادة تشغيل حركة الأربع جهات"
                            className="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-slate-200 transition-all flex items-center gap-1 text-xs font-bold"
                          >
                            <RotateCcw className="w-4 h-4" />
                            <span className="hidden sm:inline">إعادة حركة 4 جهات</span>
                          </button>
                        </div>
                      </div>

                      {/* Main Audio Graphic & Preview Frame */}
                      <div className="w-full max-w-2xl my-auto py-8 flex flex-col items-center">
                        {/* Audio Visualization disk container */}
                        <div className="relative w-40 h-40 sm:w-48 sm:h-48 rounded-full shadow-2xl border-4 border-slate-800 flex items-center justify-center overflow-hidden mb-6 group bg-slate-900">
                          {/* Rotating record of the direct image */}
                          <motion.div 
                            animate={{ rotate: 360 }}
                            transition={{ duration: 15, repeat: Infinity, ease: 'linear' }}
                            className="absolute inset-1 rounded-full bg-cover bg-center border-2 border-amber-400/40 opacity-70"
                            style={{ backgroundImage: `url(${directImageUrl})` }}
                          />
                          {/* Inner center of record */}
                          <div className="absolute w-12 h-12 rounded-full bg-slate-950 border-4 border-slate-800 flex items-center justify-center z-10">
                            <div className="w-3 h-3 rounded-full bg-amber-400" />
                          </div>
                          
                          {/* Waves around disk */}
                          <div className="absolute inset-0 rounded-full bg-indigo-500/10 animate-ping pointer-events-none" />
                        </div>

                        {/* Beautiful Waveform animation */}
                        <div className="flex gap-1.5 justify-center items-end h-12 w-full max-w-sm mb-6">
                          {[...Array(24)].map((_, i) => {
                            const heights = [
                              'h-3', 'h-8', 'h-12', 'h-6', 'h-4', 'h-10', 'h-8', 'h-5',
                              'h-11', 'h-7', 'h-3', 'h-12', 'h-10', 'h-6', 'h-4', 'h-11',
                              'h-9', 'h-5', 'h-3', 'h-10', 'h-12', 'h-7', 'h-4', 'h-8'
                            ];
                            return (
                              <div
                                key={i}
                                className={`w-1.5 rounded-full bg-gradient-to-t from-indigo-500 to-amber-400 opacity-80 ${heights[i % heights.length]} animate-[pulse_1.2s_infinite_${i * 45}ms]`}
                              />
                            );
                          })}
                        </div>

                        {/* EMBEDDED GOOGLE DRIVE PLAYER (Hidden or sized elegantly so they can control Google Drive stream) */}
                        <div className="w-full bg-slate-900/90 border border-slate-800 rounded-xl p-3 shadow-lg relative group">
                          <div className="absolute top-2 left-3 flex items-center gap-1 text-[10px] text-amber-400 font-bold bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                            <span>جاري التشغيل الآمن من Google Drive</span>
                          </div>
                          
                          <p className="text-right text-[11px] text-slate-400 font-bold mb-2">اضغط على زر التشغيل (Play) في المشغل المدمج لبدء الاستماع:</p>
                          
                          <iframe
                            src={selectedItem.url}
                            width="100%"
                            height="150px"
                            style={{ border: 'none', borderRadius: '8px', background: '#111827' }}
                            allow="autoplay"
                          />
                        </div>
                      </div>

                      {/* Bottom disclaimer & instructions */}
                      <div className="w-full text-center bg-white/5 p-4 rounded-xl border border-white/5 text-xs text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                          <Sparkles className="w-4 h-4 text-amber-400" />
                          <span>خلفية المشغل هي <strong>صورة المشروع الرسمية</strong> المعالجة متحركاً</span>
                        </div>
                        <p className="font-bold text-slate-300">
                          شركة آت يونس للتكنولوجيا والحلول الرقمية © 2026
                        </p>
                      </div>

                    </div>
                  </motion.div>
                )}

                {/* 3. STANDARD DOCUMENT VIEWER: Normal Iframe for documents and forms */}
                {selectedItem && selectedType !== 'audio' && (
                  <motion.div
                    key={`doc-viewer-${selectedItem.id}`}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="absolute inset-0 flex flex-col bg-white"
                  >
                    {/* Frame Top Bar */}
                    <div className="bg-slate-900 text-white p-4 flex justify-between items-center border-b border-slate-800">
                      <div className="flex items-center gap-2">
                        <span className="text-base font-extrabold">{selectedItem.title}</span>
                      </div>
                      <a
                        href={selectedItem.url.replace('/preview', '').replace('?embedded=true', '')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 px-3 py-1 bg-white/5 rounded-lg border border-white/10 transition-colors"
                      >
                        <span>فتح في علامة تبويب جديدة</span>
                        <ExternalLink className="w-3.5 h-3.5" />
                      </a>
                    </div>

                    {/* Actual embedded Google Document / Google Form Iframe */}
                    <iframe
                      name="viewer"
                      src={selectedItem.url}
                      width="100%"
                      className="flex-1 w-full min-h-[500px] lg:min-h-[700px] bg-slate-50 border-none"
                    />
                  </motion.div>
                )}
              </AnimatePresence>

            </div>
          </div>

          {/* Footer of the entire card */}
          <div className="p-6 sm:p-8 bg-slate-900 text-slate-400 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs font-bold">
            <div className="flex items-center gap-2">
              <span className="p-1 rounded bg-slate-800 text-slate-300">EURL</span>
              <span>مؤسسة آت يونس للتكنولوجيا والحلول الرقمية</span>
            </div>
            <div className="flex items-center gap-2">
              <span>المكتب التسويقي الحصري لمشروع المرادية</span>
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
            </div>
          </div>

        </div>
      </div>
    </div>
  );
}
