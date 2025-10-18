// server.js - example Node/Express server for PhonePe + Google Pay integration (replace placeholders with your credentials)
require('dotenv').config();
const express = require('express');
const fetch = require('node-fetch');
const bodyParser = require('body-parser');
const crypto = require('crypto');
const app = express();
app.use(bodyParser.json());
const PHONEPE_ENV = process.env.PHONEPE_ENV || 'UAT';
const PHONEPE_MERCHANT_ID = process.env.PHONEPE_MERCHANT_ID || 'YOUR_PHONEPE_MERCHANT_ID';
const PHONEPE_SECRET = process.env.PHONEPE_SECRET || 'YOUR_PHONEPE_SECRET';
const PHONEPE_BASE = (PHONEPE_ENV === 'UAT') ? 'https://merchant-api-preprod.phonepe.com' : 'https://merchant-api.phonepe.com';
function phonePeHmac(payload){ return crypto.createHmac('sha256', PHONEPE_SECRET).update(payload).digest('hex'); }
app.post('/create-order', async (req,res)=>{ try{ const { provider, cart } = req.body; let total=0; for(const pid in cart) total += cart[pid].price*cart[pid].quantity; const delivery = total <= 500 ? 12 : 24; const grand = total + delivery; const merchantOrderId = 'FF-'+Date.now(); if(provider === 'phonepe'){ const payload = { merchantId: PHONEPE_MERCHANT_ID, merchantOrderId, amount: grand*100, currency: 'INR', redirectUrl: process.env.PHONEPE_REDIRECT_URL || 'https://your-domain/phonepe-return' }; const body = JSON.stringify(payload); const signature = phonePeHmac(body); const resp = await fetch(PHONEPE_BASE + '/v3/checkout', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-VERIFY': signature, 'X-MERCHANT-ID': PHONEPE_MERCHANT_ID }, body }); const j = await resp.json(); return res.json({ success:true, checkoutUrl: j?.data?.paymentUrl || null, raw: j }); } if(provider === 'googlepay'){ return res.json({ success:true, orderId: merchantOrderId, amount: grand }); } res.status(400).json({ success:false, message:'unknown provider' }); }catch(err){console.error(err);res.status(500).json({ success:false, message: err.message });}});
app.post('/googlepay/charge', async (req,res)=>{ try{ const { token, cart } = req.body; console.log('Received Google Pay token (server):', token); return res.json({ success:true, message:'Simulated charge success' }); }catch(err){console.error(err);res.status(500).json({ success:false, message: err.message });}});
app.post('/phonepe/webhook', (req,res)=>{ console.log('PhonePe webhook received', req.headers, req.body); res.status(200).send('OK'); });
const PORT = process.env.PORT || 3000; app.listen(PORT, ()=> console.log('Server running on port', PORT));