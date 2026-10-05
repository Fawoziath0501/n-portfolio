// Libellés de l'interface publique (FR / EN), repris de la maquette.
export function labels(fr) {
  const L = fr ? {
    navAria: 'Navigation principale', langAria: 'Langue', crumbAria: 'Fil d’Ariane', menu: 'Menu', close: 'Fermer', footNav: 'Navigation', resources: 'Ressources', toTop: 'Revenir en haut', rights: 'Tous droits réservés',
    nav: ['Accueil', 'À propos', 'Projets', 'Services', 'Contact'], nav0: 'Accueil', nav2: 'Projets', ctaContact: 'Me contacter',
    fWhere: 'Basée à', fNow: 'Poste actuel', fSince: 'Expérience', fSinceV: 'Depuis 2023 · 4 postes', fLang: 'Langues', fLangV: 'Français · Anglais (A2)',
    aboutLabel: 'À propos', aboutTitle: 'Construire des produits web utiles, pas simplement écrire du code.', moreAbout: 'En savoir plus sur moi',
    workLabel: 'Projets sélectionnés', workTitle: 'Des applications réelles, du besoin au produit', workIntro: 'Applications métier, plateformes et sites développés au fil de mes expériences professionnelles.', allWork: 'Tous les projets', readCase: 'Étude de cas', filterAria: 'Filtrer les projets', fAll: 'Tous', kApp: 'Application', kSite: 'Site web',
    expLabel: 'Expérience', expTitle: 'Parcours professionnel', expIntro: 'Quatre postes en développement web fullstack, de l’e-commerce aux applications métier.', current: 'Poste actuel', fullPath: 'Voir tout le parcours',
    skillsLabel: 'Compétences', skillsTitle: 'Mon environnement technique', skillsIntro: 'Les langages, frameworks et outils que j’utilise au quotidien, regroupés par domaine.',
    servicesLabel: 'Services', servicesTitle: 'Comment je peux vous aider', allServices: 'Tous les services', askService: 'Demander ce service',
    eduLabel: 'Formation', certLabel: 'Certifications', langLabel: 'Langues', soft: 'Qualités', verify: 'Vérifier', testiTitle: 'Témoignages',
    ctaHome: 'Un projet en tête ?', ctaCase: 'Vous avez un projet similaire ? Parlons-en.', ctaText: 'Disponible pour des missions freelance, des projets web complets ou une opportunité en équipe.', availShort: 'Disponible pour missions',
    footBio: 'Je conçois des applications web concrètes, de l’analyse du besoin à la mise en ligne.',
    contactLabel: 'Contact', formTitle: 'Écrivez-moi', email: 'E-mail', phone: 'Téléphone', location: 'Localisation', hours: 'Horaires', follow: 'Suivez-moi',
    fName: 'Nom', fNamePh: 'Votre nom', emailPh: 'vous@exemple.com', subject: 'Sujet', message: 'Message',
    subjectPh: 'Mission freelance, poste, collaboration…', messagePh: 'Décrivez votre projet ou votre besoin en quelques lignes.',
    send: 'Envoyer le message', sending: 'Envoi…', sent: 'Message envoyé. Merci, je vous réponds rapidement.',
    eName: 'Indiquez votre nom.', eEmail: 'Saisissez une adresse e-mail valide.', eMsg: 'Votre message doit contenir au moins 10 caractères.',
    caseLabel: 'Étude de cas', caseNav: 'Navigation entre projets', prev: 'Projet précédent', next: 'Projet suivant', noShots: 'Captures à venir',
    mType: 'Catégorie', mRole: 'Rôle', mYear: 'Année', mLink: 'Lien',
    bContext: 'Contexte', bChallenge: 'Enjeu', bContrib: 'Ma contribution', bSolution: 'Fonctionnalités', bResult: 'Résultat',
    pAbout: ['Mon parcours', 'À propos de moi', 'Développeuse fullstack, je conçois des applications web avec Laravel et Vue.js, de la compréhension du besoin à la mise en ligne.'],
    pWork: ['Mes réalisations', 'Projets', 'Applications métier, plateformes et sites web sur lesquels j’ai travaillé.'],
    pServices: ['Ce que je propose', 'Services', 'Du développement d’application à la formation, des prestations adaptées à votre projet.'],
    pContact: ['Contact', 'Parlons de votre projet', 'Je suis disponible pour des missions freelance, des projets web complets ou des conseils techniques.']
  } : {
    navAria: 'Main navigation', langAria: 'Language', crumbAria: 'Breadcrumb', menu: 'Menu', close: 'Close', footNav: 'Navigation', resources: 'Resources', toTop: 'Back to top', rights: 'All rights reserved',
    nav: ['Home', 'About', 'Work', 'Services', 'Contact'], nav0: 'Home', nav2: 'Work', ctaContact: 'Contact me',
    fWhere: 'Based in', fNow: 'Current role', fSince: 'Experience', fSinceV: 'Since 2023 · 4 roles', fLang: 'Languages', fLangV: 'French · English (A2)',
    aboutLabel: 'About', aboutTitle: 'Building useful web products, not just writing code.', moreAbout: 'More about me',
    workLabel: 'Selected work', workTitle: 'Real applications, from need to product', workIntro: 'Business applications, platforms and websites built across my professional roles.', allWork: 'All projects', readCase: 'Case study', filterAria: 'Filter projects', fAll: 'All', kApp: 'Application', kSite: 'Website',
    expLabel: 'Experience', expTitle: 'Where I’ve worked', expIntro: 'Four fullstack web development roles, from e-commerce to business applications.', current: 'Current role', fullPath: 'See full background',
    skillsLabel: 'Skills', skillsTitle: 'My technical toolkit', skillsIntro: 'The languages, frameworks and tools I use day to day, grouped by area.',
    servicesLabel: 'Services', servicesTitle: 'How I can help', allServices: 'All services', askService: 'Request this service',
    eduLabel: 'Education', certLabel: 'Certifications', langLabel: 'Languages', soft: 'Strengths', verify: 'Verify', testiTitle: 'Testimonials',
    ctaHome: 'Got a project in mind?', ctaCase: 'Working on something similar? Let’s talk.', ctaText: 'Available for freelance work, complete web projects or a role in a team.', availShort: 'Available for projects',
    footBio: 'I build practical web applications, from understanding the need to going live.',
    contactLabel: 'Contact', formTitle: 'Send me a message', email: 'Email', phone: 'Phone', location: 'Location', hours: 'Hours', follow: 'Follow me',
    fName: 'Name', fNamePh: 'Your name', emailPh: 'you@example.com', subject: 'Subject', message: 'Message',
    subjectPh: 'Freelance project, role, collaboration…', messagePh: 'Tell me about your project or need in a few lines.',
    send: 'Send message', sending: 'Sending…', sent: 'Message sent. Thank you, I’ll get back to you soon.',
    eName: 'Please enter your name.', eEmail: 'Please enter a valid email address.', eMsg: 'Your message needs at least 10 characters.',
    caseLabel: 'Case study', caseNav: 'Project navigation', prev: 'Previous project', next: 'Next project', noShots: 'Screenshots coming soon',
    mType: 'Category', mRole: 'Role', mYear: 'Year', mLink: 'Link',
    bContext: 'Context', bChallenge: 'Challenge', bContrib: 'My contribution', bSolution: 'Features', bResult: 'Result',
    pAbout: ['My background', 'About me', 'Fullstack developer building web applications with Laravel and Vue.js, from understanding the need to going live.'],
    pWork: ['What I’ve built', 'Work', 'Business applications, platforms and websites I have worked on.'],
    pServices: ['What I offer', 'Services', 'From application development to training: services shaped around your project.'],
    pContact: ['Contact', 'Let’s talk about your project', 'I’m available for freelance work, complete web projects or technical advice.']
  };
  Object.assign(L, fr ? {
    svcEyebrow: 'Demande de service', svcService: 'Service souhaité', nameLbl: 'Nom', emailLbl: 'E-mail', phoneLbl: 'Téléphone / WhatsApp', svcWhen: 'Délai souhaité', svcWhens: ['Dès que possible', 'Dans le mois', 'Dans les 3 mois', 'Pas de date fixe'], svcNeed: 'Votre besoin', svcNeedPh: 'Décrivez le projet, le contexte et ce que vous attendez.', svcSend: 'Envoyer la demande', svcSending: 'Envoi…', svcOkTitle: 'Demande envoyée', svcOkText: 'Merci ! J’ai bien reçu votre demande et je reviens vers vous rapidement par e-mail.', cancel: 'Annuler', choose: 'Choisir…', ctaEyebrow: 'Travaillons ensemble', glance: 'En bref', onPage: 'Sur cette page', jMethod: 'Méthode', jPath: 'Parcours', jLang: 'Langues', jSkills: 'Compétences', gProjects: 'Projets', gOnline: 'En ligne', gTopics: 'Thèmes', gArticles: 'Articles', gReply: 'Délai de réponse', gReplyV: '24 h en général', gServices: 'Prestations', gFormats: 'Formats', gFormatsV: 'Freelance · Mission · Formation', gZone: 'Zone', gZoneV: 'Bénin & à distance', methodTitle: 'Ma méthode en 3 temps', flipHint: 'Survolez ou touchez une carte', statusLabel: 'Statut', heroBadge: 'Développeuse fullstack', hello: 'Bonjour, je suis', sYears: 'ans d’expérience', sProjects: 'projets réalisés', sRoles: 'expériences pro',
    aRole: 'Rôle', aLoc: 'Localisation', aStatus: 'Statut', ghTitle: 'Activité GitHub', ghRepos: 'dépôts publics', ghSince: 'membre depuis',
    iName: 'Nom complet', iLoc: 'Localisation', iLang: 'Langues', iStatus: 'Statut', iStatusV: 'Ouverte au freelance et au CDI',
    expLabel: 'Mon parcours', expTitle: 'Expériences & Formations', kCdi: 'Expérience · CDI', kCdd: 'Expérience · CDD', kStage: 'Expérience · Stage', kEdu: 'Formation',
    crumbWork: 'Mes réalisations', replyTime: 'Réponse sous 24 h en général', formTitle: 'Écrivez-moi'
  } : {
    svcEyebrow: 'Service request', svcService: 'Service', nameLbl: 'Name', emailLbl: 'Email', phoneLbl: 'Phone / WhatsApp', svcWhen: 'Preferred timing', svcWhens: ['As soon as possible', 'Within a month', 'Within 3 months', 'No fixed date'], svcNeed: 'Your need', svcNeedPh: 'Describe the project, the context and what you expect.', svcSend: 'Send request', svcSending: 'Sending…', svcOkTitle: 'Request sent', svcOkText: 'Thank you! I’ve received your request and will get back to you shortly by email.', cancel: 'Cancel', choose: 'Choose…', ctaEyebrow: 'Let’s work together', glance: 'At a glance', onPage: 'On this page', jMethod: 'Method', jPath: 'Journey', jLang: 'Languages', jSkills: 'Skills', gProjects: 'Projects', gOnline: 'Live', gTopics: 'Topics', gArticles: 'Articles', gReply: 'Response time', gReplyV: 'Usually 24 h', gServices: 'Services', gFormats: 'Formats', gFormatsV: 'Freelance · Contract · Training', gZone: 'Area', gZoneV: 'Benin & remote', methodTitle: 'My three-step method', flipHint: 'Hover or tap a card', statusLabel: 'Status', heroBadge: 'Fullstack developer', hello: 'Hi, I’m', sYears: 'years of experience', sProjects: 'projects delivered', sRoles: 'professional roles',
    aRole: 'Role', aLoc: 'Location', aStatus: 'Status', ghTitle: 'GitHub activity', ghRepos: 'public repos', ghSince: 'member since',
    iName: 'Full name', iLoc: 'Location', iLang: 'Languages', iStatus: 'Status', iStatusV: 'Open to freelance and full-time roles',
    expLabel: 'My journey', expTitle: 'Experience & Education', kCdi: 'Experience · Full-time', kCdd: 'Experience · Fixed-term', kStage: 'Experience · Internship', kEdu: 'Education',
    crumbWork: 'Work', replyTime: 'Usually replies within 24 h', formTitle: 'Send me a message'
  });
  Object.assign(L, fr ? {
    aboutShortTitle: 'Développeuse fullstack, orientée produit.',
    aboutShortText: 'Je conçois des applications web avec Laravel et Vue.js, de l’analyse du besoin jusqu’à la mise en ligne. Mon objectif : des outils fiables, simples à utiliser et faciles à faire évoluer.',
    blogLabel: 'Blog & actualités', blogTitle: 'Derniers articles', allPosts: 'Tous les articles', read: 'min de lecture',
    nlText: 'Recevez mes nouveaux articles sur le développement web et le SEO. Pas de spam, désinscription en un clic.', nlCta: 'S’abonner',
    nlOk: 'Merci ! Votre inscription est enregistrée.', nlErr: 'Saisissez une adresse e-mail valide.', nlDup: 'Cette adresse est déjà inscrite.',
    pBlog: ['Blog & actualités', 'Articles', 'Conseils et retours d’expérience sur le développement web, Laravel, Vue.js et le SEO.']
  } : {
    aboutShortTitle: 'Fullstack developer with a product mindset.',
    aboutShortText: 'I build web applications with Laravel and Vue.js, from understanding the need through to launch. My goal: reliable tools that are simple to use and easy to evolve.',
    blogLabel: 'Blog & news', blogTitle: 'Latest articles', allPosts: 'All articles', read: 'min read',
    nlText: 'Get my new articles on web development and SEO. No spam, unsubscribe in one click.', nlCta: 'Subscribe',
    nlOk: 'Thank you! You’re subscribed.', nlErr: 'Please enter a valid email address.', nlDup: 'This address is already subscribed.',
    pBlog: ['Blog & news', 'Articles', 'Tips and lessons learned on web development, Laravel, Vue.js and SEO.']
  });
  Object.assign(L, fr ? {
    cv: 'Télécharger mon CV', maintTitle: 'Site en maintenance', maintText: 'Le portfolio revient très vite. Vous pouvez me joindre par e-mail en attendant.',
    errSend: 'L’envoi a échoué. Réessayez dans un instant.'
  } : {
    cv: 'Download my CV', maintTitle: 'Under maintenance', maintText: 'The portfolio will be back shortly. Feel free to reach me by email in the meantime.',
    errSend: 'Sending failed. Please try again in a moment.'
  });
  return L;
}
