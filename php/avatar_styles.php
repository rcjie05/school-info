
        /* ── Avatar Upload Section ── */
        .avatar-upload-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 1rem;
            flex-wrap: wrap;
        }
        .avatar-preview-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .avatar-preview {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-pink), var(--primary-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 4px solid var(--border-color);
            box-shadow: var(--shadow-md);
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-initials {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            line-height: 1;
        }
        .avatar-edit-btn {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 30px;
            height: 30px;
            background: var(--primary-purple);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: transform 0.15s, background 0.15s;
            text-decoration: none;
        }
        .avatar-edit-btn:hover { transform: scale(1.15); background: var(--primary-purple-dark); }
        .avatar-upload-info {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .avatar-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .avatar-role {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: capitalize;
        }
        .avatar-hint {
            font-size: 0.78rem;
            color: var(--text-light);
            margin-top: 0.2rem;
        }
        .avatar-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.65rem;
            flex-wrap: wrap;
        }
        .avatar-actions .btn {
            padding: 0.55rem 1.1rem;
            font-size: 0.83rem;
        }
        .avatar-status {
            font-size: 0.82rem;
            margin-top: 0.4rem;
            min-height: 1.2em;
        }
