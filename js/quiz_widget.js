const microQuizData = [
    {
        question: "Что вас сейчас больше всего беспокоит?",
        options: [
            { text: "Ничего не хочет (Апатия)", icon: "fa-battery-quarter", color: "text-blue-500", score: "apathetic" },
            { text: "Не слышит и спорит", icon: "fa-bolt", color: "text-red-500", score: "rebellious" },
            { text: "Быстро бросает начатое", icon: "fa-person-running", color: "text-yellow-500", score: "impulsive" }
        ]
    },
    {
        question: "Как ребенок ведет себя в новой компании?",
        options: [
            { text: "Сразу всех строит (Лидер)", icon: "fa-crown", color: "text-purple-500", score: "leader" },
            { text: "Стоит в сторонке (Наблюдатель)", icon: "fa-eye", color: "text-green-500", score: "observer" },
            { text: "Легко вливается", icon: "fa-handshake", color: "text-blue-500", score: "social" }
        ]
    },
    {
        question: "Главная цель на ближайший год?",
        options: [
            { text: "Найти 'его' дело", icon: "fa-magnifying-glass", color: "text-emerald-500", score: "search" },
            { text: "Убрать гаджеты", icon: "fa-mobile-screen-button", color: "text-slate-500", score: "detox" },
            { text: "Сделать Чемпионом", icon: "fa-medal", color: "text-orange-500", score: "champion" }
        ]
    }
];

let currentQuestionIndex = 0;
let userAnswers = {};

function initMicroQuiz() {
    const container = document.getElementById('micro-quiz-container');
    if (!container) return;
    renderQuestion(container);
}

function renderQuestion(container) {
    const data = microQuizData[currentQuestionIndex];
    
    // Animation fade out
    container.innerHTML = `
        <div class="bg-white p-6 rounded-2xl shadow-xl border border-blue-100 max-w-md mx-auto transform transition-all duration-300 animate-fade-in-up">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-bold text-blue-500 uppercase tracking-widest">Вопрос ${currentQuestionIndex + 1}/${microQuizData.length}</span>
                <div class="flex gap-1">
                    ${microQuizData.map((_, idx) => `
                        <div class="w-12 h-1 rounded-full ${idx <= currentQuestionIndex ? 'bg-blue-500' : 'bg-slate-200'}"></div>
                    `).join('')}
                </div>
            </div>
            
            <h3 class="text-lg font-bold text-slate-800 mb-6 leading-tight">${data.question}</h3>
            
            <div class="space-y-3">
                ${data.options.map((opt, idx) => `
                    <button onclick="handleOptionClick(${idx})" class="w-full text-left p-4 rounded-xl border border-slate-100 hover:border-blue-500 hover:bg-blue-50 transition flex items-center gap-4 group">
                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center group-hover:bg-blue-500 transition">
                            <i class="fa-solid ${opt.icon} ${opt.color} group-hover:text-white text-lg transition"></i>
                        </div>
                        <span class="font-medium text-slate-600 group-hover:text-slate-900">${opt.text}</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-slate-300 group-hover:text-blue-500"></i>
                    </button>
                `).join('')}
            </div>
        </div>
    `;
}

function handleOptionClick(optionIndex) {
    const data = microQuizData[currentQuestionIndex];
    userAnswers[currentQuestionIndex] = data.options[optionIndex].score;
    
    if (currentQuestionIndex < microQuizData.length - 1) {
        currentQuestionIndex++;
        const container = document.getElementById('micro-quiz-container');
        renderQuestion(container);
    } else {
        showSuccessState();
    }
}

function showSuccessState() {
    const container = document.getElementById('micro-quiz-container');
    container.innerHTML = `
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 rounded-2xl shadow-2xl text-white max-w-md mx-auto text-center animate-fade-in-up relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10"></div>
            
            <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-check text-2xl text-white"></i>
            </div>
            
            <h3 class="text-2xl font-bold mb-2">Анализ завершен!</h3>
            <p class="text-blue-100 mb-8 leading-relaxed">
                Мы нашли неочевидные связи. Ваш ребенок обладает редким поведенческим паттерном.
            </p>
            
            <a href="assessment.html?ref=micro_quiz" class="block w-full bg-white text-blue-700 py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-blue-50 transition transform hover:-translate-y-1">
                Открыть полный отчет
            </a>
            <div class="mt-4 text-xs text-blue-200 opacity-80">
                <i class="fa-solid fa-lock"></i> Результаты сохранены
            </div>
        </div>
    `;
}

// Add CSS for animation
document.head.insertAdjacentHTML('beforeend', `
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
    </style>
`);

// Auto-init if container exists
document.addEventListener('DOMContentLoaded', initMicroQuiz);
