<?php
/**
 * Plugin Name: Smart Shop Chatbot
 * Description: Adds a floating smart chatbot to the website to assist customers.
 * Version: 1.0.0
 * Author: Smart Dev
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neve_Shop_Chatbot {
    public function __construct() {
        add_action('wp_footer', [$this, 'render_chatbot']);
    }

    public function render_chatbot() {
        ?>
        <style>
            #nsc-chatbot-btn {
                position: fixed;
                bottom: 25px;
                right: 90px;
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #6c5ce7, #a29bfe);
                border-radius: 50%;
                box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            #nsc-chatbot-btn:hover {
                transform: scale(1.1);
            }
            #nsc-chatbot-btn svg {
                fill: #fff;
                width: 30px;
                height: 30px;
            }
            
            #nsc-chatbot-window {
                position: fixed;
                bottom: 100px;
                right: 90px;
                width: 350px;
                height: 450px;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                display: flex;
                flex-direction: column;
                z-index: 999999;
                overflow: hidden;
                transform: scale(0);
                transform-origin: bottom right;
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            #nsc-chatbot-window.nsc-active {
                transform: scale(1);
            }

            .nsc-header {
                background: linear-gradient(135deg, #6c5ce7, #a29bfe);
                color: #fff;
                padding: 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .nsc-header-info h4 {
                margin: 0;
                font-family: 'Inter', sans-serif;
                font-size: 16px;
                font-weight: 600;
                color: #fff;
            }
            .nsc-header-info p {
                margin: 4px 0 0;
                font-size: 12px;
                opacity: 0.9;
            }
            .nsc-close {
                cursor: pointer;
                background: rgba(255,255,255,0.2);
                border-radius: 50%;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.3s;
            }
            .nsc-close:hover {
                background: rgba(255,255,255,0.4);
            }

            .nsc-body {
                flex: 1;
                background: #f8f9fa;
                padding: 15px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            
            .nsc-msg {
                max-width: 80%;
                padding: 12px 16px;
                border-radius: 16px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                line-height: 1.4;
                animation: nscFadeIn 0.3s ease;
            }
            .nsc-msg-bot {
                background: #fff;
                color: #2d3436;
                align-self: flex-start;
                border-bottom-left-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.04);
            }
            .nsc-msg-user {
                background: #6c5ce7;
                color: #fff;
                align-self: flex-end;
                border-bottom-right-radius: 4px;
                box-shadow: 0 2px 5px rgba(108, 92, 231, 0.2);
            }

            @keyframes nscFadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .nsc-footer {
                padding: 15px;
                background: #fff;
                border-top: 1px solid #eee;
                display: flex;
                gap: 10px;
            }
            .nsc-input {
                flex: 1;
                border: none;
                background: #f1f2f6;
                padding: 12px 16px;
                border-radius: 20px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                outline: none;
            }
            .nsc-send {
                background: #6c5ce7;
                border: none;
                width: 42px;
                height: 42px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: 0.3s;
            }
            .nsc-send:hover {
                background: #5a4bd1;
            }
            .nsc-send svg {
                fill: #fff;
                width: 18px;
                height: 18px;
                margin-left: 2px;
            }
            .nsc-typing {
                font-size: 12px;
                color: #b2bec3;
                align-self: flex-start;
                display: none;
            }
        </style>

        <!-- Chatbot Button -->
        <div id="nsc-chatbot-btn">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 5.92 2 10.75c0 2.76 1.47 5.22 3.75 6.78V22l3.44-1.9c.9.24 1.84.37 2.81.37 5.52 0 10-3.92 10-8.75S17.52 2 12 2zm0 15c-1.03 0-2.03-.18-2.96-.5l-.26-.09-2.02 1.12.56-1.92-.18-.24C5.23 14.1 4 12.51 4 10.75 4 6.99 7.59 4 12 4s8 2.99 8 6.75-3.59 6.75-8 6.75z"/><circle cx="8.5" cy="10.5" r="1.5"/><circle cx="15.5" cy="10.5" r="1.5"/></svg>
        </div>

        <!-- Chatbot Window -->
        <div id="nsc-chatbot-window">
            <div class="nsc-header">
                <div class="nsc-header-info">
                    <h4>Smart Assistant</h4>
                    <p>Typically replies instantly</p>
                </div>
                <div class="nsc-close" id="nsc-close-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </div>
            </div>
            <div class="nsc-body" id="nsc-body">
                <div class="nsc-msg nsc-msg-bot">
                    مرحباً بك في متجرنا! 👋 كيف يمكنني مساعدتك اليوم؟
                </div>
                <div class="nsc-typing" id="nsc-typing">يكتب الآن...</div>
            </div>
            <div class="nsc-footer">
                <input type="text" class="nsc-input" id="nsc-input" placeholder="اكتب رسالتك هنا..." />
                <button class="nsc-send" id="nsc-send-btn">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('nsc-chatbot-btn');
                const win = document.getElementById('nsc-chatbot-window');
                const closeBtn = document.getElementById('nsc-close-btn');
                const input = document.getElementById('nsc-input');
                const sendBtn = document.getElementById('nsc-send-btn');
                const body = document.getElementById('nsc-body');
                const typing = document.getElementById('nsc-typing');

                btn.addEventListener('click', () => {
                    win.classList.toggle('nsc-active');
                    if(win.classList.contains('nsc-active')) {
                        input.focus();
                    }
                });

                closeBtn.addEventListener('click', () => {
                    win.classList.remove('nsc-active');
                });

                function appendMessage(text, sender) {
                    const msg = document.createElement('div');
                    msg.className = `nsc-msg nsc-msg-${sender}`;
                    msg.innerText = text;
                    body.insertBefore(msg, typing);
                    body.scrollTop = body.scrollHeight;
                }

                function handleSend() {
                    const text = input.value.trim();
                    if(!text) return;
                    
                    appendMessage(text, 'user');
                    input.value = '';
                    typing.style.display = 'block';
                    body.scrollTop = body.scrollHeight;

                    setTimeout(() => {
                        typing.style.display = 'none';
                        const replies = [
                            "شكراً لتواصلك معنا! موظفونا متاحون قريباً للرد عليك.",
                            "هذا رائع! هل يمكنك توضيح سؤالك أكثر؟",
                            "نحن هنا دائماً في خدمتك، يمكنك تصفح المنتجات في صفحة المتجر في هذا الوقت.",
                            "تم استلام رسالتك! مشروع مميز جداً نتمنى لك التوفيق 🚀"
                        ];
                        const reply = replies[Math.floor(Math.random() * replies.length)];
                        appendMessage(reply, 'bot');
                    }, 1200);
                }

                sendBtn.addEventListener('click', handleSend);
                input.addEventListener('keypress', (e) => {
                    if(e.key === 'Enter') handleSend();
                });
            });
        </script>
        <?php
    }
}

new Neve_Shop_Chatbot();
